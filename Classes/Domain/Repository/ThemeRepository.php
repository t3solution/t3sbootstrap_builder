<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * @extends Repository<\T3SBS\T3sbootstrapBuilder\Domain\Model\Theme>
 */
class ThemeRepository extends Repository
{
    protected $defaultOrderings = ['title' => QueryInterface::ORDER_ASCENDING];

    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findBySiteIdentifier(string $siteIdentifier): ?\T3SBS\T3sbootstrapBuilder\Domain\Model\Theme
    {
        $query = $this->createQuery();
        $query->matching($query->equals('siteIdentifier', $siteIdentifier));
        return $query->execute()->getFirst();
    }
}
