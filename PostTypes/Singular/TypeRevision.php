<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Singular;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts\SinglePostAbstract;
use function is_int;

class TypeRevision extends SinglePostAbstract
{
    private bool $postInit = false;

    public function getPost(): ?Post
    {
        /** @noinspection DuplicatedCode */
        if ($this->postInit) {
            return $this->post;
        }
        $this->postInit = true;
        if ($this->post) {
            return $this->post;
        }
        $this->postInit = true;
        $this->post = null;
        $post = is_int($this->identity)
            ? $this->module->findPostById($this->identity)
            : $this->module->findPostBySlug($this->identity);
        if ($post?->isRevision()) {
            $this->post = $post;
        }
        return $this->post;
    }
}
