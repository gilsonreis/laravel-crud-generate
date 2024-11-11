<?php

namespace App\Filters;

class ProductFilter extends BaseFilter
{
    public function __construct(
        private ?string $name,
        private ?string $description,
        private ?string $tags,
        private ?string $name_slug,

    ) {
        parent::__construct();

    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getNameSlug(): ?string
    {
        return $this->name_slug;
    }

    public function setNameSlug(?string $name_slug): self
    {
        $this->name_slug = $name_slug;
        return $this;
    }


    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'tags' => $this->getTags(),
            'name_slug' => $this->getNameSlug(),
        ];
    }
}