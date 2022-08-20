<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request                $request,
        EntityManagerInterface $manager,
    ): Response

    {
        $contact = new Contact();

        if ($this->getUser()) {
            $contact->setFullName($this->getUser()->getFullname())
                ->setEmail($this->getUser()->getEmail());
        }

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->getData();

            $manager->persist($contact);
            $manager->flush();

            # email
            $transport = Transport::fromDsn('smtp://localhost:1025');
            $mailer = new Mailer($transport);

            $email = (new TemplatedEmail())
                ->from($contact->getEmail())
                ->to('admin@symrecipy.com')
                ->subject($contact->getSubject())
                ->html($contact->getMessage());
                /*DON'T WORK !!!*/
                //->htmlTemplate('/emails/contact.html.twig');
               /* ->context([
                        'contact' => $contact
                ]);*/

            $mailer->send($email);

            $this->addFlash(
                'success',
                'Votre demande a été envoyé avec succès !'
            );
            return $this->redirectToRoute('app_contact');

        } else {

            $this->addFlash(
                'success',
                'Votre message a été envoyé avec succès!'
            );

        }

        return $this->render('pages/contact/index.html.twig', [
                'form' => $form->createView(),
            ]);
        }
}
