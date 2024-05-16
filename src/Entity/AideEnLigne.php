<?php

namespace App\Entity;

use App\Repository\AideEnLigneRepository;
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

    #[ORM\ManyToOne]
    private ?CategorieAide $categorie = null;

    #[ORM\ManyToOne]
    private ?SousCategorieAide $sousCategorie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

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

    public function getCategorie(): ?CategorieAide
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieAide $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getSousCategorie(): ?SousCategorieAide
    {
        return $this->sousCategorie;
    }

    public function setSousCategorie(?SousCategorieAide $SousCategorie): static
    {
        $this->sousCategorie = $SousCategorie;

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
