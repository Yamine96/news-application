<?php

namespace App\Controller;

use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use App\Service\NavCategory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use dateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/news')]
class NewsController extends AbstractController
{
    private NavCategory $navCategory;
    public function __construct(NavCategory $navCategory)
    {
        $this->navCategory = $navCategory;
    }
    #[Route('/', name: 'app_news_index', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): Response
    {
        return $this->render('news/index.html.twig', [
            'news' => $newsRepository->findAll(),
            'categoryList'=>$this->navCategory->category()
        ]);
    }

    #[Route('/new', name: 'app_news_new', methods: ['GET', 'POST'])]
    public function new(Request $request,
                        NewsRepository $newsRepository,
                        EntityManagerInterface $manager
    ): Response
    {  if (!$this->getUser()){
        return $this->redirectToRoute('app_login');
    }
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setAuthor($this->getUser());
            $news->setUpdatetedAt(new dateTime);
            $newsRepository->save($news, true);

            $this->addFlash('succes',"your news has been sent");

            $news = $form->getData();
            if($request->files->get('news')['image']) {
                $image = $request->files->get('news')['image'];
                $image_name = time() . '_' . $image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'), $image_name);
                $news->setImage($image_name);
            }
            $manager->persist($news);
            $manager->flush();

            return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('news/new.html.twig', [
            'news' => $news,
            'form' => $form,
            'categoryList'=>$this->navCategory->category()
        ]);
    }

    #[Route('/{id}', name: 'app_news_show', methods: ['GET'])]
    public function show(News $news): Response
    {
        return $this->render('news/show.html.twig', [
            'news' => $news,
            'categoryList'=>$this->navCategory->category()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_news_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, News $news, NewsRepository $newsRepository): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setUpdatetedAt(new dateTime);
            $newsRepository->save($news, true);

            $this->addFlash('success',"your news has been modified");

            return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('news/edit.html.twig', [
            'news' => $news,
            'form' => $form,
            'categoryList'=>$this->navCategory->category()
        ]);
    }

    #[Route('/{id}', name: 'app_news_delete', methods: ['POST'])]
    public function delete(Request $request, News $news, NewsRepository $newsRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->request->get('_token'))) {
            $newsRepository->remove($news, true);
        }
        $this->addFlash('success',"your item has been deleted");

        return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
    }

    /*=== Search Bar ===*/
    public function searchForm (){
        $form= $this->createFormBuilder()
            ->setMethod('GET')
            ->add('text',TextType::class,[
                'attr'=>[
                    'placeholder'=>'Search for news',
                    'required'=>false,
                    'class' => 'form-control'
                ]
            ])
            ->getForm();
        return $this->render('news/searchBar.html.twig',[
            'searchForm'=>$form->createView()
        ]);

    }

    /**
     * @Route("/handleSearch", name="handleSearch")
     * @param Request $request
     */
    public function handleSearch(Request $request, NewsRepository $repo)
    {

        $formValues = $request->get('form');
        $query= $formValues['text'];

        if($query) {
            $news= $repo->findNewsByName(trim($query));

            return $this->render('news/index.html.twig', [
                'news' => $news
            ]);
        }
    }

}
