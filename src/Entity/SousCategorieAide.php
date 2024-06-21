<?php

namespace App\Entity;

use App\Repository\SousCategorieAideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SousCategorieAideRepository::class)]
class SousCategorieAide
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intitule = null;

    /**
     * @var Collection<int, AideEnLigne>
     */
    #[ORM\ManyToMany(targetEntity: AideEnLigne::class, mappedBy: 'sousCategorie')]
    private Collection $aideEnLignes;

    /**
     * @var Collection<int, CategorieAide>
     */
    #[ORM\ManyToMany(targetEntity: CategorieAide::class, inversedBy: 'sousCategorieAides')]
    private Collection $categorieAide;

    public function __construct()
    {
        $this->aideEnLignes = new ArrayCollection();
        $this->categorieAide = new ArrayCollection();
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

    /**
     * @return Collection<int, AideEnLigne>
     */
    public function getAideEnLignes(): Collection
    {
        return $this->aideEnLignes;
    }

    public function addAideEnLigne(AideEnLigne $aideEnLigne): static
    {
        if (!$this->aideEnLignes->contains($aideEnLigne)) {
            $this->aideEnLignes->add($aideEnLigne);
            $aideEnLigne->addSousCategorie($this);
        }

        return $this;
    }

    public function removeAideEnLigne(AideEnLigne $aideEnLigne): static
    {
        if ($this->aideEnLignes->removeElement($aideEnLigne)) {
            $aideEnLigne->removeSousCategorie($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CategorieAide>
     */
    public function getCategorieAide(): Collection
    {
        return $this->categorieAide;
    }

    public function addCategorieAide(CategorieAide $categorieAide): static
    {
        if (!$this->categorieAide->contains($categorieAide)) {
            $this->categorieAide->add($categorieAide);
        }

        return $this;
    }

    public function removeCategorieAide(CategorieAide $categorieAide): static
    {
        $this->categorieAide->removeElement($categorieAide);

        return $this;
    }





}
