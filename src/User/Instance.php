<?php
/**
 * SPT software - User Instance
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: User Adapter
 * 
 */

namespace SPT\User;

use SPT\User\Adapter UserAdapter;

class Instance
{
    private $adapter;
    public function __construct(UserAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function get(string $key)
    {
        return $this->adapter->get($key);
    }

    public function can(string $key)
    {
        return $this->adapter->can($key);
    }
}