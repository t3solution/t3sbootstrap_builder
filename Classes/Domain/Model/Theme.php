<?php
declare(strict_types=1);

namespace T3SBS\T3sbootstrapBuilder\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Theme extends AbstractEntity
{
    protected string $title = '';
    protected string $siteIdentifier = '';
    protected int $rootPageUid = 0;
    protected string $basePreset = '';
    protected string $variablesJson = '';
    protected string $customScss = '';

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getSiteIdentifier(): string { return $this->siteIdentifier; }
    public function setSiteIdentifier(string $v): void { $this->siteIdentifier = $v; }

    public function getRootPageUid(): int { return $this->rootPageUid; }
    public function setRootPageUid(int $v): void { $this->rootPageUid = $v; }

    public function getBasePreset(): string { return $this->basePreset; }
    public function setBasePreset(string $v): void { $this->basePreset = $v; }

    public function getVariablesJson(): string { return $this->variablesJson; }
    public function setVariablesJson(string $v): void { $this->variablesJson = $v; }

    /** @return array<string, string> */
    public function getVariables(): array
    {
        $decoded = json_decode($this->variablesJson ?: '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getCustomScss(): string { return $this->customScss; }
    public function setCustomScss(string $v): void { $this->customScss = $v; }
}
