<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Mark;
use App\Entity\Recipe;
use App\Form\IngredientType;
use App\Form\MarkType;
use App\Form\RecipeType;
use App\Repository\IngredientRepository;
use App\Repository\MarkRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\DebugUnitOfWorkListener;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


class RecipeController extends AbstractController
{
    #[Route('/recette', name: 'app_recipe', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(RecipeRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $recipes = $paginator->paginate(
            $repository->findBy(['user' => $this->getUser()]),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/recipe/index.html.twig', [
            'recipes' => $recipes
        ]);
    }

    #[Route('/recette/publique', name: 'recipe.index_public', methods: ['GET'])]
    public function indexPublic(
        PaginatorInterface $paginator,
        RecipeRepository $repository,
        Request $request

    ): Response{

        $recipes = $paginator->paginate(
            $repository->findPublicRecipe(null),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/recipe.index_public.html.twig', [
            'recipes' => $recipes
        ]);
    }

    #[Security("is_granted('ROLE_USER') and recipe.isIsPublic() === true")]
    #[Route('/recette/{id}', 'recipe.show', methods: ['GET', 'POST'])]
    public function show(Recipe $recipe, Request $request, MarkRepository $markRepository, EntityManagerInterface $manager): Response {

        $mark = new Mark();

        $form = $this->createForm(MarkType::class, $mark);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $mark->setUser($this->getUser())
            ->setRecipe($recipe);

            $existingMark = $markRepository->findOneBy([
                    'user' => $this->getUser(),
                    'recipe' => $recipe,
            ]
            );

            if(!$existingMark){
                $manager->persist($mark);
            } else {
                $existingMark->setMark(
                    $form->getData()->getMark()
                );
            }

            $manager->flush();

            $this->addFlash(
                'success',
                'Votre notre à bien été prise en compte'
            );

            return $this->redirectToRoute('recipe.show', ['id' => $recipe->getId()]);
         
        }



        return $this->render('pages/recipe/show.html.twig', [
            'recipe' => $recipe,
            'form' => $form->createView()
        ]);
    }

    #[Route('/recette/nouveau', name: 'recipe.new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $manager,
    ) : Response{
       $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // dd($form->getData());
            $recipe = $form->getData();
            $recipe->setUser($this->getUser());

            $manager->persist($recipe);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre recette a été ajouté avec succès!'
            );

            return $this->redirectToRoute('app_recipe');
        }

        return $this->render('pages/recipe/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
    #[Route('/recette/edition/{id}', name: 'recipe.edit', methods: ['GET', 'POST'])]
    public function edit(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $recipe = $form->getData();

            $manager->persist($recipe);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre recette a été modifiée avec succès!'
            );

            return $this->redirectToRoute('app_recipe');
        }
        return $this->render('pages/recipe/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/recette/suppression/{id}', 'recipe.delete', methods: ['GET', 'DELETE'])]
    public function delete(EntityManagerInterface $manager, Recipe $recipe): Response
    {


        $manager->remove($recipe);
        $manager->flush();

        $this->addFlash(
            'success',
            'Votre recette a été supprimée avec succès!'
        );

        return $this->redirectToRoute('app_recipe');
    }
}
