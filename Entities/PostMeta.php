<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractBasedMeta;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Post metadata',
        'priority' => 101,
        'primaryKey' => [
            'id',
            'name'
        ]
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['post_id'],
    name: 'relation_post_meta_post_id_posts_id'
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $post_id
 * @property-read Post $post
 */
class PostMeta extends AbstractBasedMeta
{
    public const TABLE_NAME = 'post_meta';
    #[Id]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $id;

    #[
        JoinColumn(
            name: 'post_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_post_meta_post_id_posts_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: Post::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected Post $post;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function getPostId(): int
    {
        return $this->post_id;
    }

    public function setPostId(int $post_id): void
    {
        $this->post_id = $post_id;
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
        $this->setPostId($post->getId());
    }

    public function getPost(): Post
    {
        return $this->post;
    }
}
