<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Security;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SvgEventListenerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        mkdir($this->instancePath . '/svg-event-listener-test');
    }

    protected function tearDown(): void
    {
        @unlink(Environment::getConfigPath() . '/system/file-storages.yaml');
        GeneralUtility::rmdir($this->instancePath . '/svg-event-listener-test', true);
        parent::tearDown();
    }

    #[Test]
    public function addedSvgIsSanitizedByTargetFileNameIfSourceFileHasNoExtension(): void
    {
        $storage = $this->createStorage();

        $file = $storage->addFile($this->createUploadedFile($this->getUnsanitizedSvg()), $storage->getRootLevelFolder(false), 'image.svg');

        self::assertStringNotContainsString('<script', $file->getContents());
    }

    #[Test]
    public function replacedSvgIsSanitizedByFileNameIfSourceFileHasNoExtension(): void
    {
        $storage = $this->createStorage();
        $file = $storage->addFile($this->createUploadedFile('<svg xmlns="http://www.w3.org/2000/svg"></svg>'), $storage->getRootLevelFolder(false), 'image.svg');

        $file = $storage->replaceFile($file, $this->createUploadedFile($this->getUnsanitizedSvg()));

        self::assertStringNotContainsString('<script', $file->getContents());
    }

    /**
     * Mime-type detection only searches the beginning of a file for an
     * <svg> element, a larger leading comment hides it from the detection.
     */
    private function getUnsanitizedSvg(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<!-- ' . str_repeat('x', 4200) . ' -->' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
    }

    /**
     * Like PHP does for uploads, the file is placed without any extension.
     */
    private function createUploadedFile(string $content): string
    {
        $filePath = tempnam($this->instancePath . '/svg-event-listener-test', 'php');
        file_put_contents($filePath, $content);
        return $filePath;
    }

    private function createStorage(): ResourceStorage
    {
        $basePath = $this->instancePath . '/svg-event-listener-test/storage';
        mkdir($basePath);
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage('storage', $basePath);
        $this->get(FileStorageConfigurationWriter::class)->write($uid, [
            'name' => 'storage',
            'configuration' => ['basePath' => $basePath . '/'],
        ]);
        $storageRepository->flush();
        return $storageRepository->findByUid($uid);
    }
}
