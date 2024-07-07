<?php

namespace App\Entity;

use App\Repository\AideEnLigneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AideEnLigneRepository::class)]
class AideEnLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;



    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texte = null;



    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permission = null;

    /**
     * @var Collection<int, SousCategorieAide>
     */
    #[ORM\ManyToMany(targetEntity: SousCategorieAide::class, inversedBy: 'aideEnLignes')]
    private Collection $sousCategorie;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    public function __construct()
    {
        $this->sousCategorie = new ArrayCollection();
    }






    public function getId(): ?int
    {
        return $this->id;
    }



    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(?string $texte): static
    {
        $this->texte = $texte;

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
    public function getSousCategorie(): Collection
    {
        return $this->sousCategorie;
    }

    public function addSousCategorie(SousCategorieAide $sousCategorie): static
    {
        if (!$this->sousCategorie->contains($sousCategorie)) {
            $this->sousCategorie->add($sousCategorie);
        }

        return $this;
    }

    public function removeSousCategorie(SousCategorieAide $sousCategorie): static
    {
        $this->sousCategorie->removeElement($sousCategorie);

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }




}
