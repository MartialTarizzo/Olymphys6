<?php

namespace App\Entity;

use App\Repository\CategorieAideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieAideRepository::class)]
class CategorieAide
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intitule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permission = null;

    /**
     * @var Collection<int, SousCategorieAide>
     */
    #[ORM\ManyToMany(targetEntity: SousCategorieAide::class, mappedBy: 'categorieAide')]
    private Collection $sousCategorieAides;

    public function __construct()
    {
        $this->sousCategorieAides = new ArrayCollection();
    }


    public function __toString() : string{
        return $this->intitule;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(?string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(?string $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * @return Collection<int, SousCategorieAide>
     */
    public function getSousCategorieAides(): Collection
    {
        return $this->sousCategorieAides;
    }

    public function addSousCategorieAide(SousCategorieAide $sousCategorieAide): static
    {
        if (!$this->sousCategorieAides->contains($sousCategorieAide)) {
            $this->sousCategorieAides->add($sousCategorieAide);
            $sousCategorieAide->addCategorieAide($this);
        }

        return $this;
    }

    public function removeSousCategorieAide(SousCategorieAide $sousCategorieAide): static
    {
        if ($this->sousCategorieAides->removeElement($sousCategorieAide)) {
            $sousCategorieAide->removeCategorieAide($this);
        }

        return $this;
    }



}
