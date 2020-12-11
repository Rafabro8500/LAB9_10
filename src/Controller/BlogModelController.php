<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\DBAL\Driver\Connection;

class BlogModelController extends AbstractController
{

    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    public function get_featured_posts()
    {
        $posts_query  = "SELECT microposts . * , users.name
        FROM microposts
        LEFT JOIN users ON microposts.user_id = users.id ORDER BY microposts.likes DESC LIMIT 10";
        return $this->connection->fetchAll($posts_query);
        
    }

    public function get_recent_posts()
    {
        $recent_posts_query  = "SELECT microposts . * , users.name
        FROM microposts
        LEFT JOIN users ON microposts.user_id = users.id ORDER BY microposts.created_at DESC LIMIT 10";
        return $this->connection->fetchAll($recent_posts_query);
    }
}
