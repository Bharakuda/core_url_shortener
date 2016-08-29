<?php

namespace AppBundle\Controller;

use AppBundle\Service\ShortCodeGenerator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\ShortLink;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * ShortLink controller.
 *
 * @Route("/shortlink")
 */
class ShortLinkController extends Controller
{
    /**
     * Creates a new ShortLink entity.
     *
     * @Route("/", name="new_shortlink")
     * @Method({"POST"})
     */
    public function newAction(Request $request)
    {
        // start session
        $session = $this->get('session');

        //create new shor link entity
        $shortLink = new ShortLink();

        // fetch long url
        $url = $_POST['longUrl'];

        // long url validation
        if (empty($url)) {
            $this->addFlash('error', 'No URL was supplied.');
            return $this->redirectToRoute('homepage');
        }

        // set long url
        $shortLink->setLongUrl($url);

        // generate short url
        $shortCodeObject = new ShortCodeGenerator();
        $shortCode = $shortCodeObject->generateShortCode();
        $shortLink->setShortCode($shortCode);

        // create complete shortened link
        $shortUrl = $this->get('router')->generate('shortlink_display', array('code' => $shortCode), 0);
        $shortLink->setShortUrl($shortUrl);

        // set ip address
        $shortLink->setIpAddress($request->getClientIp());

        // if user is anonymous
        if($this->get('security.token_storage')->getToken()->getUser() == "anon."){

            // save short link to database without user id
            $em = $this->getDoctrine()->getManager();
            $em->persist($shortLink);
            $em->flush();

            // If user is anonymous, after you save url in DB, then add it to session
            $anonymousUrls = $session->get('anonymous_urls');

            if (!$anonymousUrls) {
                $anonymousUrls = array();
            }

            $anonymousUrls[] = $shortLink;
            $session->set('anonymous_urls', $anonymousUrls);

            return $this->redirectToRoute('homepage');
        }

        // associate link to registered user
        $loggedUserId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $shortLink->setUserId($loggedUserId);

        // save short link to database
        $em = $this->getDoctrine()->getManager();
        $em->persist($shortLink);
        $em->flush();

        // display shortened url in table below main form
        return $this->redirectToRoute('homepage');
    }

    /**
     * Finds and displays a ShortLink entity.
     *
     * @Route("/{id}", name="shortlink_show")
     * @Method("GET")
     */
    public function showAction(ShortLink $shortLink)
    {
        $deleteForm = $this->createDeleteForm($shortLink);

        return $this->render('@App/shortlink/show.html.twig', array(
            'shortLink' => $shortLink,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ShortLink entity.
     *
     * @Route("/{id}/edit", name="shortlink_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ShortLink $shortLink)
    {
        $deleteForm = $this->createDeleteForm($shortLink);
        $editForm = $this->createForm('AppBundle\Form\ShortLinkType', $shortLink);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($shortLink);
            $em->flush();

            return $this->redirectToRoute('shortlink_edit', array('id' => $shortLink->getId()));
        }

        return $this->render('shortlink/edit.html.twig', array(
            'shortLink' => $shortLink,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a ShortLink entity.
     *
     * @Route("/{id}", name="shortlink_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, ShortLink $shortLink, $id)
{
    $form = $this->createDeleteForm($shortLink);
    $form->handleRequest($request);


    if ($form->isSubmitted() && $form->isValid()) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($shortLink);
        $em->flush();
    }

    return $this->redirectToRoute('homepage');
}

    /**
     * Creates a form to delete a ShortLink entity.
     *
     * @param ShortLink $shortLink The ShortLink entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ShortLink $shortLink)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('shortlink_delete', array('id' => $shortLink->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
