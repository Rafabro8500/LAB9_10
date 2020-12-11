<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Controller\BlogModelController;

class BlogController extends AbstractController
{

    private $blog_model;
    private $session;
    private $validator;

    public function __construct(BlogModelController $blog_model, SessionInterface $session, ValidatorInterface $validator)
    {
        $this->blog_model = $blog_model;
        $this->session = $session;
        $this->validator = $validator;
    }

    /**
     * @Route("/blog", name="blog")
     */
    public function index(): Response
    {
        $data['posts'] = $this->blog_model->get_featured_posts();
        $data['recent_posts'] = $this->blog_model->get_recent_posts();
        if($this->session->get('user_id')){

        }
        return $this->render('blog/index_template.html.twig', $data);
    }

    /**
     * @Route("/post/{post_id?}", name="post")
     */
    public function post($post_id = null): Response
    {
        return new Response(
            "Chegámos ao post $post_id! ",
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
    }
}
