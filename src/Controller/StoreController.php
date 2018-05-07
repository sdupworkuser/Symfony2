<?php
namespace Acme\StoreBundle\Controller;

use Doctrine\Common\Util\Debug;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Acme\StoreBundle\Document\Product;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends Controller
{
    /**
     * This method is responsible to return the product list
     */
    public function indexAction()
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        /** @var $repository \Acme\StoreBundle\Repository\ProductRepository */
        $repository = $dm->getRepository('Product');
        $products = $repository->findAllOrderedByName();
        return $this->render('index.html.twig', array('products' => $products));
    }

    /**
     * This method is responsible for adding new product
     * params, $name, string, product name
     * params, $price, float, product price
     */
    public function createAction($name, $price)
    {
        $product = new Product();
        $product->setName($name);
        $product->setPrice($price);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($product);
        $dm->flush();
        return $this->redirect($this->generateUrl('acme_store_show', array('id' => $product->getId())));
    }

    /**
     * This method returns the product of given id
     * params, $id, int, product id
     */
    public function showAction($id)
    {
        $product = $this->get('doctrine_mongodb')
            ->getRepository('Product')
            ->find($id);
        if (!$product) {
            throw $this->createNotFoundException('No product found for id '.$id);
        }
        return $this->render('show.html.twig', array('product' => $product));
    }

    /**
     * This method is responsible for updating the product name by id
     * params, $id, int, product id
     * params, $name, string, product name
     */
    public function updateAction($id, $name)
    {
        /** @var $dm \Doctrine\ODM\MongoDB\DocumentManager */
        $dm = $this->get('doctrine_mongodb')->getManager();
        /** @var $product Product */
        $product = $dm->getRepository('Product')->find($id);
        if (!$product) {
            throw $this->createNotFoundException('No product found for id ' . $id);
        }
        $product->setName($name);
        $dm->flush();
        return $this->redirect($this->generateUrl('acme_store_show', array('id' => $id)));
    }

    /**
     * @Route("/contact", name="_demo_contact")
     * @Template()
     */
    public function contactAction()
    {
        $form = $this->get('form.factory')->create(new ContactType());
        $request = $this->get('request');
        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $mailer = $this->get('mailer');
                // .. setup a message and send it
                // http://symfony.com/doc/current/cookbook/email.html
                $this->get('session')->setFlash('notice', 'Message sent!');
                return new RedirectResponse($this->generateUrl('_demo'));
            }
        }
        return array('form' => $form->createView());
    }
}