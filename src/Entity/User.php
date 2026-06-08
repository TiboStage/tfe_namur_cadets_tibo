<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '"user"')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'registration.email.unique')]
#[UniqueEntity(fields: ['username'], message: 'registration.username.unique')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ─── Champs d'authentification ────────────────────────────────────────────
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'registration.email.not_blank')]
    #[Assert\Email(message: 'registration.email.invalid')]
    public string $email = '' {
        set => $this->email = strtolower(trim((string) $value));
    }
    /**
     * Nom d'utilisateur public affiché sur la plateforme.
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'registration.username.not_blank')]
    #[Assert\Length(min: 3, max: 30, minMessage: 'registration.username.too_short', maxMessage: 'registration.username.too_long')]
    #[Assert\Regex(pattern: '/^[a-z0-9_-]+$/', message: 'registration.username.invalid_chars')]
    public string $username = '' {
        set => $this->username = strtolower(trim((string) $value));
    }
    /**
     * Rôles Symfony stockés en JSON.
     * Toujours au moins ["ROLE_USER"].
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    // ─── Champs profil ────────────────────────────────────────────────────────

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'registration.firstname.not_blank')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'registration.lastname.not_blank')]
    private ?string $lastName = null;

    /**
     * Courte biographie / description publique du créateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    /**
     * Nom du fichier avatar uploadé (stocké dans /uploads/avatars/).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    /**
     * Couleur hexadécimale de l'avatar généré (ex: '#3B82F6').
     * Choisie depuis la palette lors de l'inscription (hash du username).
     */
    #[ORM\Column(length: 7)]
    private string $avatarColor = '#3B82F6';

    /** Palette de 8 couleurs pour les avatars générés. */
    private const AVATAR_PALETTE = [
        '#3B82F6', // bleu
        '#8B5CF6', // violet
        '#EC4899', // rose
        '#F97316', // orange
        '#10B981', // vert émeraude
        '#06B6D4', // cyan
        '#EAB308', // jaune/or
        '#6366F1', // indigo
    ];

    /**
     * Génère une couleur déterministe depuis le username.
     * Utilise abs(crc32) pour éviter les valeurs négatives.
     */
    public static function generateAvatarColor(string $username): string
    {
        $index = abs(crc32(strtolower(trim($username)))) % count(self::AVATAR_PALETTE);
        return self::AVATAR_PALETTE[$index];
    }

    // ─── Champs modération ────────────────────────────────────────────────────

    #[ORM\Column]
    private bool $isBanned = false;

    // ─── Préférences ──────────────────────────────────────────────────────────

    /**
     * Langue préférée de l'utilisateur — utilisée pour les pages d'erreur
     * et l'interface. PHP 8.4 property hook avec validation intégrée.
     */
    #[ORM\Column(length: 5)]
    public string $locale = 'fr' {
        get => $this->locale;
        set {
            if (!in_array($value, ['fr', 'en', 'nl'])) {
                throw new \InvalidArgumentException("Locale invalide : $value");
            }
            $this->locale = $value;
        }
    }

    // ─── Timestamps ───────────────────────────────────────────────────────────

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * Date du dernier changement de nom d'utilisateur.
     * Null = jamais changé → toujours autorisé.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $usernameChangedAt = null;

    /** Nombre de jours de cooldown entre deux changements de pseudo. */
    public const USERNAME_COOLDOWN_DAYS = 30;

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * Projets créés par cet utilisateur.
     *
     * mappedBy: 'createdBy' → correspond à la PROPRIÉTÉ $createdBy dans Project.
     *
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'createdBy', orphanRemoval: true)]
    private Collection $projects;

    /**
     * Utilisateurs qui ont aimé ce créateur.
     * La table de jointure user_likes lie liked_id → liker_id.
     *
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'likes')]
    #[ORM\JoinTable(
        name: 'user_likes',
        joinColumns: [new ORM\JoinColumn(name: 'liked_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'liker_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $likedBy;

    /**
     * Créateurs que cet utilisateur a aimés.
     *
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'likedBy')]
    private Collection $likes;

    // ─── Constructeur ─────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->likedBy  = new ArrayCollection();
        $this->likes    = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── UserInterface ────────────────────────────────────────────────────────

    /**
     * Identifiant unique utilisé par Symfony Security (l'email).
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     * Données sensibles déjà effacées via __serialize().
     */
    public function eraseCredentials(): void
    {
        // Si tu stockes un mot de passe en clair temporairement, efface-le ici.
        // $this->plainPassword = null;
    }

    /**
     * Hachage CRC32C du mot de passe en session (Symfony 7.3+).
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password ?? '');
        return $data;
    }

    // ─── Getters & Setters ────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * Nom complet affiché dans l'interface.
     */
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): static
    {
        $this->avatarFilename = $avatarFilename;
        return $this;
    }

    public function getAvatarColor(): string
    {
        return $this->avatarColor;
    }

    public function setAvatarColor(string $avatarColor): static
    {
        $this->avatarColor = $avatarColor;
        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getUsernameChangedAt(): ?\DateTimeImmutable
    {
        return $this->usernameChangedAt;
    }

    public function setUsernameChangedAt(?\DateTimeImmutable $usernameChangedAt): static
    {
        $this->usernameChangedAt = $usernameChangedAt;
        return $this;
    }

    /**
     * Vérifie si l'utilisateur peut changer son pseudo (cooldown expiré ou jamais changé).
     */
    public function canChangeUsername(): bool
    {
        if ($this->usernameChangedAt === null) {
            return true;
        }
        $cooldownEnd = $this->usernameChangedAt->modify('+' . self::USERNAME_COOLDOWN_DAYS . ' days');
        return new \DateTimeImmutable() >= $cooldownEnd;
    }

    /**
     * Retourne le nombre de jours restants avant de pouvoir changer son pseudo.
     * Retourne 0 si le changement est autorisé.
     */
    public function getDaysUntilNextUsernameChange(): int
    {
        if ($this->canChangeUsername()) {
            return 0;
        }
        $cooldownEnd = $this->usernameChangedAt->modify('+' . self::USERNAME_COOLDOWN_DAYS . ' days');
        return (int) (new \DateTimeImmutable())->diff($cooldownEnd)->days + 1;
    }

    // ─── Collection $projects ─────────────────────────────────────────────────

    /** @return Collection<int, Project> */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCreatedBy($this);
        }
        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getCreatedBy() === $this) {
                $project->setCreatedBy(null);
            }
        }
        return $this;
    }

    // ─── Collection $likedBy / $likes ─────────────────────────────────────────

    /** @return Collection<int, self> */
    public function getLikedBy(): Collection
    {
        return $this->likedBy;
    }

    /** Nombre de personnes qui ont aimé ce créateur. */
    public function getLikesCount(): int
    {
        return $this->likedBy->count();
    }

    /** L'utilisateur $liker a-t-il déjà aimé ce créateur ? */
    public function isLikedBy(self $liker): bool
    {
        return $this->likedBy->contains($liker);
    }

    public function addLikedBy(self $liker): static
    {
        if (!$this->likedBy->contains($liker)) {
            $this->likedBy->add($liker);
            $liker->likes->add($this);
        }
        return $this;
    }

    public function removeLikedBy(self $liker): static
    {
        if ($this->likedBy->removeElement($liker)) {
            $liker->likes->removeElement($this);
        }
        return $this;
    }

    /** @return Collection<int, self> */
    public function getLikes(): Collection
    {
        return $this->likes;
    }
}
