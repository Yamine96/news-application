<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
#use http\Env\Request;
use App\Service\NavCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    private NavCategory $navCategory;
    public function __construct(NavCategory $navCategory)
    {
        $this->navCategory = $navCategory;
    }
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $contact=new Contact();
        $form = $this->createForm(ContactType::class,$contact);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($contact);
            $entityManager->flush();
            $email = new Email();
            $email->from($contact->getEmail());
            $email->to($this->getParameter('app.contact.email'));
            $email->replyTo($this->getParameter('app.contact.email'));
            $email->subject($this->getParameter('app.contact.subject'));
            $email->html($contact->getMessage());
            $mailer->send($email);
            $this->addFlash('success','your message has been sent');
            return $this->redirectToRoute('app_home_page');
        }
        return $this->render('contact/index.html.twig', [
            'contact_address'=>$this->getParameter('app.contact.address'),
            'contact_phone'=>$this->getParameter('app.contact.phone'),
            'contact_email'=>$this->getParameter('app.contact.email'),
            'form'=>$form->createView(),
            'categoryList'=>$this->navCategory->category()

        ]);
    }
}
