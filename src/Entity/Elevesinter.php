<?php

namespace App\Entity;

use App\Repository\ElevesinterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;

#[ORM\Entity(repositoryClass: ElevesinterRepository::class)]
class Elevesinter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $numsite = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classe = null;

    #[ORM\ManyToOne]
    private ?Equipesadmin $equipe;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $courriel = null;

    #[ORM\OneToOne(targetEntity: Fichiersequipes::class, mappedBy: 'eleve', cascade: ['persist', 'remove'])]
    private ?Fichiersequipes $autorisationphotos;

    public function __toString()
    {
        return $this->getNomPrenomlivre();

    }

    public function getNomPrenomlivre(): string
    {
        if ($this->equipe->getSelectionnee() == true) {
            $NomPrenom = $this->equipe->getNumero() . '-' . $this->equipe->getLettre() . '-' . $this->nom . ' ' . $this->prenom;
        }
        if ($this->equipe->getSelectionnee() == false) {
            $NomPrenom = $this->equipe->getNumero() . '-' . $this->nom . ' ' . $this->prenom;
        }
        return $NomPrenom;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNumsite(): ?int
    {
        return $this->numsite;
    }


    public function setNumsite($numsite)
    {
        $this->numsite = $numsite;
    }


    public function getNom(): ?string
    {
        return $this->nom;
    }


    public function setNom(?string $nom): Elevesinter
    {
        $this->nom = $nom;

        return $this;
    }


    public function getPrenom(): ?string
    {
        return $this->prenom;
    }


    public function setPrenom(?string $prenom): Elevesinter
    {
        $this->prenom = $prenom;

        return $this;
    }


    public function getClasse(): ?string
    {
        return $this->classe;
    }


    public function setClasse(?string $classe): Elevesinter
    {
        $this->classe = $classe;

        return $this;
    }

    public function getEquipe(): ?Equipesadmin
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipesadmin $Equipe): Elevesinter
    {
        $this->equipe = $Equipe;

        return $this;
    }

    public function getCourriel(): ?string
    {
        return $this->courriel;
    }

    public function setCourriel(?string $courriel): Elevesinter
    {
        $this->courriel = $courriel;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): Elevesinter
    {
        $this->genre = $genre;

        return $this;
    }

    public function getAutorisationphotos(): ?fichiersequipes
    {
        return $this->autorisationphotos;
    }

    public function setAutorisationphotos(?Fichiersequipes $autorisation): Elevesinter
    {
        $this->autorisationphotos = $autorisation;

        return $this;
    }

    public function getNomPrenom(): ?string
    {

        return $this->nom . ' ' . $this->prenom;
    }

    public function getFichiersequipes(): ?Fichiersequipes
    {
        return $this->fichiersequipes;
    }

    public function setFichiersequipes(?Fichiersequipes $fichiersequipes): self
    {
        // unset the owning side of the relation if necessary
        if ($fichiersequipes === null && $this->fichiersequipes !== null) {
            $this->fichiersequipes->setEleve(null);
        }

        // set the owning side of the relation if necessary
        if ($fichiersequipes !== null && $fichiersequipes->getEleve() !== $this) {
            $fichiersequipes->setEleve($this);
        }

        $this->fichiersequipes = $fichiersequipes;

        return $this;
    }


}

