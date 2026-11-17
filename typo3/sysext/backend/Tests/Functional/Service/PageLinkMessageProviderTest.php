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

namespace TYPO3\CMS\Backend\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Service\PageLinkMessageProvider;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageLinkMessageProviderTest extends FunctionalTestCase
{
    #[Test]
    public function linkToCurrentPageEscapesAppendedQueryParameters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $pageRecord = [
            'uid' => 1,
            'doktype' => PageRepository::DOKTYPE_LINK,
            'link' => 't3://page?uid=current&foo=<svg/onload=alert(1)>',
        ];

        $result = $this->get(PageLinkMessageProvider::class)
            ->generateMessagesForPageTypeLink($pageRecord, new ServerRequest('https://example.com/typo3/'));

        self::assertStringNotContainsString('<svg', $result['message']);
        self::assertStringContainsString('t3://page?uid=current&amp;foo=&lt;svg/onload=alert(1)&gt;', $result['message']);
    }
}
