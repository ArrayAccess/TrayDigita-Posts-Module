<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\Entities;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Site;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use ArrayAccess\TrayDigita\Database\Entities\Traits\ParentIdEventStateTrait;
use ArrayAccess\TrayDigita\Database\Entities\Traits\PasswordTrait;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use ArrayAccess\TrayDigita\Util\Generator\UUID;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use function strtolower;
use function trim;

/**
 * @property-read int $id
 * @property-read string $slug
 * @property-read ?int $site_id
 * @property-read string $title
 * @property-read string $content
 * @property-read string $type
 * @property-read ?int $category_id
 * @property-read string $status
 * @property-read ?int $parent_id
 * @property-read ?int $user_id
 * @property-read ?string $password
 * @property-read bool $password_protected
 * @property-read ?DateTimeInterface $published_at
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?Admin $user
 * @property-read ?Post $parent
 * @property-read ?PostCategory $category
 * @property-read ?Site $site
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Table posts',
        'priority' => 100,
    ]
)]
#[UniqueConstraint(
    name: 'unique_slug_site_id',
    columns: ['slug', 'site_id']
)]
#[Index(
    columns: [
        'type',
        'status',
        'site_id',
        'id',
    ],
    name: 'index_type_status_id_site_id'
)]
#[Index(
    columns: [
        'title',
        'site_id',
        'type',
        'status',
        'id',
        'parent_id',
        'user_id',
        'published_at',
        'created_at',
        'deleted_at',
        'password_protected',
    ],
    name: 'index_like_search_sorting'
)]
#[Index(
    columns: ['published_at', 'created_at'],
    name: 'index_published_at_created_at'
)]
#[Index(
    columns: ['site_id'],
    name: 'relation_posts_site_id_sites_id'
)]
#[Index(
    columns: ['category_id'],
    name: 'index_category_id'
)]
#[Index(
    columns: ['category_id', 'site_id'],
    name: 'relation_posts_category_id_post_categories_id_site_id'
)]
#[Index(
    columns: ['parent_id'],
    name: 'relation_posts_parent_id_posts_id'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_posts_user_id_admins_id'
)]
#[HasLifecycleCallbacks]
class Post extends AbstractEntity implements AvailabilityStatusEntityInterface
{
    public const TABLE_NAME = 'posts';

    use AvailabilityStatusTrait,
        PasswordTrait,
        ParentIdEventStateTrait;

    public const TYPE_POST = 'post';

    public const TYPE_PAGE = 'page';

    public const TYPE_REVISION = 'revision';

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key post id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'slug',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Post slug'
        ]
    )]
    protected string $slug;

    #[Column(
        name: 'site_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Site id'
        ]
    )]
    protected ?int $site_id = null;

    #[Column(
        name: 'title',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Post Title'
        ]
    )]
    protected string $title;

    #[Column(
        name: 'content',
        type: Types::TEXT,
        length: 4294967295,
        nullable: false,
        options:  [
            'default' => '',
            'comment' => 'Post content'
        ]
    )]
    protected string $content = '';

    #[Column(
        name: 'type',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options:  [
            'default' => 'post',
            'comment' => 'Post type'
        ]
    )]
    protected string $type = self::TYPE_POST;

    #[Column(
        name: 'category_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Category id'
        ]
    )]
    protected ?int $category_id = null;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        options: [
            'default' => self::DRAFT,
            'comment' => 'Post status'
        ]
    )]
    protected string $status = self::DRAFT;

    #[Column(
        name: 'parent_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'comment' => 'Post parent id'
        ]
    )]
    protected ?int $parent_id = null;

    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Admin id'
        ]
    )]
    protected ?int $user_id = null;

    #[Column(
        name: 'password',
        type: Types::STRING,
        length: 255,
        nullable: true,
        updatable: true,
        options: [
            'default' => null,
            'comment' => 'Post password'
        ]
    )]
    protected ?string $password = null;

    #[Column(
        name: 'password_protected',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => 'Protect post with password'
        ]
    )]
    protected bool $password_protected = false;

    #[Column(
        name: 'published_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Date published'
        ]
    )]
    protected ?DateTimeInterface $published_at = null;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_MUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Post created time'
        ]
    )]
    protected DateTimeInterface $created_at;

    #[Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE,
        unique: false,
        updatable: false,
        options: [
            'attribute' => 'ON UPDATE CURRENT_TIMESTAMP',
            'default' => '0000-00-00 00:00:00',
            'comment' => 'Post update time'
        ],
        // columnDefinition: "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP"
    )]
    protected DateTimeInterface $updated_at;

    #[Column(
        name: 'deleted_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Post delete time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'parent_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_posts_parent_id_posts_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: self::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Post $parent = null;

    #[JoinTable(name: PostCategory::TABLE_NAME)]
    #[JoinColumn(
        name: 'category_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
        options: [
            'relation_name' => 'relation_posts_category_id_post_categories_id_site_id',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'SET NULL'
        ],
    )]
    #[JoinColumn(
        name: 'site_id',
        referencedColumnName: 'site_id',
        nullable: true,
        onDelete: 'SET NULL',
        options: [
            'relation_name' => 'relation_posts_category_id_post_categories_id_site_id',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'SET NULL'
        ],
    )]
    #[
        ManyToOne(
            targetEntity: PostCategory::class,
            cascade: [
                'persist'
            ],
            fetch: 'EAGER'
        )
    ]
    protected ?PostCategory $category = null;

    #[
        JoinColumn(
            name: 'site_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_posts_site_id_sites_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        ManyToOne(
            targetEntity: Site::class,
            cascade: [
                "persist"
            ],
            fetch: 'EAGER'
        )
    ]
    protected ?Site $site = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_posts_user_id_admins_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: Admin::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Admin $user = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
    }

    #[
        PrePersist,
        PreUpdate
    ]
    public function preCheckSlug(PrePersistEventArgs|PreUpdateEventArgs $event): void
    {
        /** @noinspection DuplicatedCode */
        $oldSlug = null;
        $slug = $this->getSlug();
        $isUpdate = $event instanceof PreUpdateEventArgs;
        if ($isUpdate) {
            if (!$event->hasChangedField('slug')) {
                return;
            }
            $oldSlug = $event->getOldValue('slug');
            $slug = $event->getNewValue('slug')?:$slug;
        }

        if ($oldSlug === $slug) {
            return;
        }

        if (trim($slug) === '') {
            $slug = UUID::v4();
        }
        do {
            $this->slug = $slug;
            $query = $event
                ->getObjectManager()
                ->getRepository($this::class)
                ->matching(
                    Expression::criteria()->where(
                        Expression::andX(
                            Expression::eq('slug', $slug),
                            Expression::eq('site_id', $this->getSite()),
                        )
                    )->setMaxResults(1)
                )
                ->count();
        } while ($query > 0 && ($slug = UUID::v4()));
        if ($isUpdate) {
            $event->setNewValue('slug', $slug);
        }
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return string
     */
    public static function normalizeType(string $type): string
    {
        $lower = strtolower(trim($type));
        return match ($lower) {
            self::TYPE_POST,
            self::TYPE_PAGE,
            self::TYPE_REVISION => $lower,
            default => trim($type)
        };
    }

    public function getNormalizeType(): string
    {
        return static::normalizeType($this->getType());
    }

    public function isRevision() : bool
    {
        return $this->getNormalizeType() === self::TYPE_REVISION
            && $this->getParent()
            && $this->getParent()->getNormalizeType() !== self::TYPE_REVISION;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCategoryId(): ?int
    {
        return $this->category_id;
    }

    public function setCategoryId(?int $category_id): void
    {
        $this->category_id = $category_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function setParentId(?int $parent_id): void
    {
        $this->parent_id = $parent_id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function isPasswordProtected(): bool
    {
        return $this->password_protected;
    }

    public function setPasswordProtected(bool $password_protected): void
    {
        $this->password_protected = $password_protected;
    }

    public function getPublishedAt(): ?DateTimeInterface
    {
        return $this->published_at;
    }

    public function setPublishedAt(?DateTimeInterface $published_at): void
    {
        $this->published_at = $published_at;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt) : void
    {
        $this->deleted_at = $deletedAt;
    }

    public function getParent(): ?Post
    {
        return $this->parent;
    }

    public function setParent(?Post $parent): void
    {
        $this->parent = $parent;
        $this->setParentId($parent?->getId());
    }

    public function getCategory(): ?PostCategory
    {
        return $this->category;
    }

    public function setCategory(?PostCategory $category): void
    {
        $this->category = $category;
        $this->setCategoryId($category?->getId());
    }

    public function getUser(): ?Admin
    {
        return $this->user;
    }

    public function setUser(?Admin $user): void
    {
        $this->user = $user;
        $this->setUserId($user->getId());
    }

    public function getSiteId(): ?int
    {
        return $this->site_id;
    }

    public function setSiteId(?int $site_id): void
    {
        $this->site_id = $site_id;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): void
    {
        $this->site = $site;
        $this->setSiteId($site?->getId());
    }

    #[
        PreUpdate,
        PostLoad,
        PrePersist
    ]
    public function checkDataEvent(
        PrePersistEventArgs|PostLoadEventArgs|PreUpdateEventArgs $event
    ) : void {
        $this->passwordBasedIdUpdatedAt($event);
        $this->parentIdCheck($event);
        $normalizeStatus = $this->getNormalizedStatus();
        $normalizeType = $this->getNormalizeType();
        $isStatusMatch = $this->getStatus() === $normalizeStatus;
        $isTypeMatch = $this->getType() === $normalizeType;
        $isMatch = $isTypeMatch && $isStatusMatch;
        $isCatSiteIdMissMatch = ($cat = $this->getCategory()) && $cat->getSiteId() !== $this->getSiteId();
        if ($isMatch && !$isCatSiteIdMissMatch) {
            return;
        }
        $this->setType($normalizeType);
        $this->setStatus($normalizeStatus);
        if ($isCatSiteIdMissMatch) {
            $this->setCategory(null);
        }
        if ($event instanceof PostLoadEventArgs) {
            $date = $this->getUpdatedAt();
            $date = str_starts_with($date->format('Y'), '-')
                ? '0000-00-00 00:00:00'
                : $date->format('Y-m-d H:i:s');
            $args = [
                'type' => $normalizeType,
                'status' => $normalizeStatus,
                'updated_at' => $date,
                'id' => $this->getId()
            ];
            $qb = $event
                ->getObjectManager()
                ->createQueryBuilder()
                ->update($this::class, 'x')
                ->set('x.type', ':type')
                ->set('x.status', ':status')
                ->set('x.updated_at', ':updated_at');
            if ($isCatSiteIdMissMatch) {
                $qb->set('x.category_id', ':cat_id');
                $args['cat_id'] = null;
            }
            // use query builder to make sure updated_at still same
            $qb
                ->where('x.id = :id')
                ->setParameters($args)
                ->getQuery()
                ->execute();
        }
    }
}
