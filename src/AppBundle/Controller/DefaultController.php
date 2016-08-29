<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Entity\ShortLink;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use UserBundle\Entity\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Constraints\UrlValidator;

class DefaultController extends Controller
{

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $session = $this->get('session');

//        var_dump($this->get('security.token_storage')->getToken()->getUser());
//        die();

//        var_dump($test); die();

        $em = $this->getDoctrine()->getManager();

        // get logged user name
        $loggedUserName = $this->get('security.token_storage')->getToken()->getUser();

        // if user is anon.
        if($loggedUserName == "anon."){
            // get all the links from database for anon. user

            $links = $em->getRepository('AppBundle:ShortLink')
                ->findById($session->get('anonymous_urls'));
        } else {
            // get all the links from database for logged user
            $loggedUserId = $this->get('security.token_storage')->getToken()->getUser()->getId();
            $links = $em->getRepository('AppBundle:ShortLink')
                ->findBy(array(
                    'userId' => $loggedUserId
                ));
        }

        return $this->render('@App/default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
            'links' => $links
        ]);
    }

    /**
     * Use short url to redirect
     *
     * @Route("/url/{code}", name="shortlink_display")
     * @Method({"GET"})
     */
    public function displayAction($code)
    {
//        // if user is anon. get log url from session and redirect to it
//        if($this->get('security.token_storage')->getToken()->getUser() == "anon."){
//            $session = $this->get('session')->get('anonymous_urls');
//
//            var_dump($session); die();
//            return $this->redirect($session->get('anonymous_urls'));
//        }

        // fetch long url from database based on code
        $em = $this->getDoctrine()->getManager();
        $url = $em->getRepository('AppBundle:ShortLink')
            ->findOneBy(array('shortCode' => $code));

        // increment counter
        $url->incrementCounter();

        // save counter to database
        $em->persist($url);
        $em->flush();

        return $this->redirect($url->getLongUrl());
    }

    /**
     * Display contact us form
     *
     * @Route("/contact", name="contact_us")
     */
    public function contactAction(Request $request)
    {
        // create form for User message
        $form = $this->createFormBuilder()
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // send email message
            $message = \Swift_Message::newInstance(null)
                ->setSubject('Contact Us Message from URLShortener.loc')
                ->setFrom('test.testiranje5@gmail.com')
                ->setTo('test.testiranje5@gmail.com')
                ->setBody(
                    $this->renderView('@User/Default/email_contact_form.html.twig',
                        array(
                            'name' => $form->get('name')->getData(),
                            'email' => $form->get('email')->getData(),
                            'message' => $form->get('message')->getData()
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            $this->addFlash('success', 'Message is sent!');
            return $this->redirectToRoute('homepage');
        }

        return $this->render('@App/default/contact.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
