<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Singular;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;

class TypePage extends TypePost
{
    protected string $postType = Post::TYPE_PAGE;
}
