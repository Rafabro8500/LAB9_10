<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Controller\BlogModelController;
use Doctrine\DBAL\Abstraction\Result;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie;

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
        if ($this->session->get('user_id')) {
            $data['user_name'] = $this->session->get('user_name');
            $data['user_id'] = $this->session->get('user_id');
        }
        return $this->render('blog/index_template.html.twig', $data);
    }

    /**
     * @Route("/blog/login", name="login")
     */
    public function login()
    {
        if ($this->session->get('user_id')) return $this->redirectToRoute('blog');
        if ($this->session->get('errors') > 0) {
            $data['errors'] = $this->session->get('errors');
            $data['email'] = $this->session->get('email');
            $data['errorMessages'] = $this->session->get('errorMessages');
            $this->session->set('errors', 0);
        } else {
            $data['errors'] = 0;
            $data['email'] = '';
        }
        if ($this->session->get('remember_me')) {
            $cookie = $this->session->get('remember_me');
            $user = $this->blog_model->get_user($cookie->getValue());
            $this->session->set('user_name', $user["name"]);
            $this->session->set('user_id', $user['id']);
            return $this->redirectToRoute('blog');
        }else{
            return $this->render('blog/login_template.html.twig', $data);
        }
    }

    /**
     * @Route("/blog/login_action", name="login_action")
     */
    public function login_action(Request $request)
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $password_digest = substr(md5($password), 0, 32);

        $user = $this->blog_model->login($email, $password_digest);

        if ($user == false) $value = '';
        else $value = $password;

        $input = ['password' => $password,  'email' => $email];

        $constraints = new Assert\Collection([
            'email' => [new Assert\NotBlank(['message' => "Email is empty!"])],
            'password' => [
                new Assert\notBlank(['message' => "Password is empty!"]),
                new Assert\EqualTo(['value' => $value, 'message' => "Wrong username or password"])
            ],
        ]);

        $data = $this->requestValidation($input, $constraints);

        if ($data['errors'] > 0) {

            $this->session->set('email', $email);
            $this->session->set('errors', $data['errors']);
            $this->session->set('errorMessages', $data['errorMessages']);
            return $this->redirectToRoute('login');
        }

        if ($request->request->get('rememberMe')) {
            $remember_digest = substr(md5(time()), 0, 32);
            $remember_cookie = new Cookie('remember_me', $remember_digest, time() + (3600 * 24 * 30));
            $result = $this->blog_model->remember_me($remember_digest, $user['id']);
            if ($result) {
                $this->session->set('remember_me', $remember_cookie);
            }
        }

        $this->session->set('user_name', $user["name"]);
        $this->session->set('user_id', $user['id']);

        return $this->redirectToRoute('blog');
    }
    /**
     * @Route("/blog/logout", name="logout")
     */
    public function logout()
    {
        $this->session->remove('user_name');
        $this->session->remove('user_id');
        $data['message_head'] = "Logged out!";
        $data['message_body'] = "Be back soon!";
        return $this->render('blog/message_template.html.twig', $data);
    }

    /**
     * @Route("/blog/register", name="register")
     */
    public function register()
    {
        if ($this->session->get('userid')) return $this->redirectToRoute('blog');
        if ($this->session->get('errors') > 0) {
            $data['errors'] = $this->session->get('errors');
            $data['email'] = $this->session->get('email');
            $data['first_name'] = $this->session->get('first_name');
            $data['last_name'] = $this->session->get('last_name');
            $data['errorMessages'] = $this->session->get('errorMessages');
            $this->session->set('errors', 0);
        } else {
            $data['errors'] = 0;
            $data['email'] = '';
            $data['first_name'] = '';
            $data['last_name'] = '';
        }

        return $this->render('blog/register_template.html.twig', $data);
    }

    /**
     * @Route("/blog/register_action", name="register_action")
     */
    public function register_action(Request $request)
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $passconf = $request->request->get('password_confirm');
        $password_digest = substr(md5($password), 0, 32);
        $first_name = $request->request->get('first_name');
        $last_name = $request->request->get('last_name');
        $name = $first_name . " " . $last_name;

        $email_query = $this->blog_model->get_email($email);

        if ($email_query == false)
            $value = 'error';
        else
            $value = $email_query['email'];

        $input = ['password' => $password, 'passconf' => $passconf,  'email' => $email, 'first_name' => $first_name, 'last_name' => $last_name];

        $constraints = new Assert\Collection([
            'first_name' => [new Assert\NotBlank(['message' => "First Name can't be empty!"])],
            'last_name' => [new Assert\NotBlank(['message' => "Last Name can't be empty!"])],
            'email' => [
                new Assert\NotBlank(['message' => "Email is empty!"]),
                new Assert\NotEqualTo(['value' => $value, 'message' => "This email already exists!"])
            ],
            'password' => [
                new Assert\notBlank(['message' => "Password is empty!"]),
                new Assert\EqualTo(['value' => $passconf, 'message' => "Passwords don't match!"])
            ],
            'passconf' => [new Assert\notBlank(['message' => "Password Confirmation must not be blank"])],
        ]);

        $data = $this->requestValidation($input, $constraints);

        if ($data['errors'] > 0) {
            $this->session->set('email', $email);
            $this->session->set('first_name', $first_name);
            $this->session->set('last_name', $last_name);
            $this->session->set('errors', $data['errors']);
            $this->session->set('errorMessages', $data['errorMessages']);
            return $this->redirectToRoute('register');
        }

        $result = $this->blog_model->register($email, $name, $password_digest);
        if ($result) {
            $data['message_head'] = "Register Successful!";
            $data['message_body'] = "Thank you for becoming a member of Portal do Cientismo! You may login now.";
            return $this->render('blog/message_template.html.twig', $data);
        } else {
            $data['message_head'] = "Registration Failed :(";
            $data['message_body'] = "There was an internal server error while trying to register your account. Please try again later.";
            return $this->render('blog/message_template.html.twig', $data);
        }
    }

    private function requestValidation($input, $constraints)
    {

        $violations = $this->validator->validate($input, $constraints);

        $errorMessages = [];

        if (count($violations) > 0) {

            $accessor = PropertyAccess::createPropertyAccessor();

            foreach ($violations as $violation) {
                $accessor->setValue(
                    $errorMessages,
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
            }
        }
        $data['errors'] = count($violations);
        $data['errorMessages'] = $errorMessages;

        return $data;
    }

    /**
     * @Route("/blog/post/{post_id?}", name="post")
     */
    public function post($post_id = null)
    {
        if (!$this->session->get('user_id')) {
            $data['message_head'] = "Error!";
            $data['message_body'] = "Please login first!";
            return $this->render('blog/message_template.html.twig', $data);
        } else {
            $data['user_name'] = $this->session->get('user_name');
            if ($post_id != null) {
                $post = $this->blog_model->get_post($post_id);
                if ($post['user_id'] != $this->session->get('user_id')) {
                    $data['message_head'] = "Error: Not allowed!";
                    $data['message_body'] = "This post does not belong to you!";
                    return $this->render('blog/message_template.html.twig', $data);
                } else {
                    $data['blog'] = $post['content'];
                    $data['post_id'] = $post['id'];
                    return $this->render('blog/blog_template.html.twig', $data);
                }
            } else {
                $data['blog'] = '';
                return $this->render('blog/blog_template.html.twig', $data);
            }
        }
    }

    /**
     * @Route("/blog/post_action/{post_id?}", name="post_action")
     */
    public function post_action(Request $request, $post_id = null)
    {
        if (!$this->session->get('user_id')) {
            $data['message_head'] = "Error!";
            $data['message_body'] = "Please login first!";
            return $this->render('blog/message_template.html.twig', $data);
        } else {
            $data['user_name'] = $this->session->get('user_name');
            if ($post_id != null) { //update post
                $post = $this->blog_model->get_post($post_id);
                if ($post['user_id'] != $this->session->get('user_id')) {
                    $data['message_head'] = "Error: Not allowed!";
                    $data['message_body'] = "This post does not belong to you!";
                    return $this->render('blog/message_template.html.twig', $data);
                } else {
                    $blog = $request->request->get('blog');
                    $result = $this->blog_model->update_post($post_id, $blog);
                    if ($result == false) {
                        $data['message_head'] = "Internal Error :(";
                        $data['message_body'] = "Failed to update post, try again later please.";
                        return $this->render('blog/message_template.html.twig', $data);
                    } else {
                        $data['message_head'] = "Success!";
                        $data['message_body'] = "Your post was updated successfully!";
                        return $this->render('blog/message_template.html.twig', $data);
                    }
                }
            } else { //create post
                $blog = $request->request->get('blog');
                $user_id = $this->session->get('user_id');
                $result = $this->blog_model->create_post($user_id, $blog);
                if ($result == false) {
                    $data['message_head'] = "Internal Error :(";
                    $data['message_body'] = "Failed to create post, try again later please.";
                    return $this->render('blog/message_template.html.twig', $data);
                } else {
                    $data['message_head'] = "Success!";
                    $data['message_body'] = "Your post was created successfully!";
                    return $this->render('blog/message_template.html.twig', $data);
                }
            }
        }
    }
}
