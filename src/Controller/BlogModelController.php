<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\DBAL\Driver\Connection;
use PhpParser\Node\Expr\FuncCall;

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

    public function login($email, $password_digest)
    {
        $query = "SELECT * FROM users WHERE email = '$email' AND password_digest = '$password_digest'";
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function get_email($email)
    {
        $query = " SELECT * FROM users WHERE email = '$email' ";
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function get_post($post_id)
    {
        $query = "SELECT * FROM microposts WHERE id = '$post_id'";
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update_post($post_id, $blog)
    {
        $present_date = date("Y-m-d H:i:s");
        $sql_update = "UPDATE microposts SET content = '$blog', updated_at = '$present_date' WHERE id = '$post_id'";
        $stmt = $this->connection->prepare($sql_update);
        return $stmt->execute();
    }

    public function create_post($user_id, $blog)
    {
        $present_date = date("Y-m-d H:i:s");
        $sql_insert = "INSERT INTO microposts (content, user_id, created_at, updated_at)
                 VALUES('$blog', '$user_id','$present_date','$present_date')";
        $stmt = $this->connection->prepare($sql_insert);
        return $stmt->execute();
    }

    public function remember_me($remember_digest, $user_id){
        $sql_update = "UPDATE users SET remember_digest = '$remember_digest' WHERE id = '$user_id'";
        $stmt = $this->connection->prepare($sql_update);
        return $stmt->execute();
    }

    public function get_user($remember_digest){
        $sql = "SELECT * FROM users WHERE remember_digest = '$remember_digest'";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function register($email, $user_name, $password_digest)
    {
        $present_date = date("Y-m-d H:i:s");
        $sql_insert = "INSERT INTO users(name, email, created_at, updated_at, password_digest)
                 VALUES('$user_name', '$email','$present_date','$present_date', '$password_digest')";
        $stmt = $this->connection->prepare($sql_insert);
        return $stmt->execute();
    }
}
