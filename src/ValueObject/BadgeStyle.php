<?php

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class BadgeStyle
{
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $borderStyle = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $borderColor = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $backgroundColor = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $textColor = null;

    public function __construct(
        ?string $borderStyle = null,
        ?string $borderColor = null,
        ?string $backgroundColor = null,
        ?string $textColor = null
    ) {
        $this->borderStyle = $borderStyle;
        $this->borderColor = $borderColor;
        $this->backgroundColor = $backgroundColor;
        $this->textColor = $textColor;
    }

    public function getBorderStyle(): ?string
    {
        return $this->borderStyle;
    }

    public function getBorderColor(): ?string
    {
        return $this->borderColor;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }
}