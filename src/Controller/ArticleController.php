<?php
namespace App\Controller;
use App\Entity\Comment;
use App\Entity\Post;
use App\Events;
use App\Form\CommentType;
use App\Repository\PostRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/blog")
 *
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, name="blog_index")
     * @Method("GET")
     * This method is responsible to load the dynamic page
     * page name and format are received as input parameters
     */
    public function index(int $page, string $_format, PostRepository $posts): Response
    {
        $latestPosts = $posts->findLatest($page);
        return $this->render('blog/index.'.$_format.'.twig', ['posts' => $latestPosts]);
    }
    /**
     * @Route("/posts/{slug}", name="blog_post")
     * @Method("GET")
     */
    public function postShow(Post $post): Response
    {
        return $this->render('blog/post_show.html.twig', ['post' => $post]);
    }
    /**
     * @Route("/comment/{postSlug}/new", name="comment_new")
     * @Method("POST")
     * This method is responsible for adding new comments
     *
     */
    public function commentNew(Request $request, Post $post, EventDispatcherInterface $eventDispatcher): Response
    {
        $comment = new Comment();
        $comment->setAuthor($this->getUser());
        $post->addComment($comment);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
            $event = new GenericEvent($comment);
            $eventDispatcher->dispatch(Events::COMMENT_CREATED, $event);
            return $this->redirectToRoute('blog_post', ['slug' => $post->getSlug()]);
        }
        return $this->render('blog/comment_form_error.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * This method is responsible to display the comment form
     */
    public function commentForm(Post $post): Response
    {
        $form = $this->createForm(CommentType::class);
        return $this->render('blog/_comment_form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/search", name="blog_search")
     * @Method("GET")
     * This method is responsible to search the articles by user entered text
     */
    public function search(Request $request, PostRepository $posts): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->render('blog/search.html.twig');
        }
        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);
        $foundPosts = $posts->findBySearchQuery($query, $limit);
        $results = [];
        foreach ($foundPosts as $post) {
            $results[] = [
                'title' => htmlspecialchars($post->getTitle(), ENT_COMPAT | ENT_HTML5),
                'date' => $post->getPublishedAt()->format('M d, Y'),
                'author' => htmlspecialchars($post->getAuthor()->getFullName(), ENT_COMPAT | ENT_HTML5),
                'summary' => htmlspecialchars($post->getSummary(), ENT_COMPAT | ENT_HTML5),
                'url' => $this->generateUrl('blog_post', ['slug' => $post->getSlug()]),
            ];
        }
        return $this->json($results);
    }
}