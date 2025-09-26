<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Entity\ServiceTranslation;
use App\Form\Type\ServiceType;
use App\Repository\LanguageRepository;
use App\Repository\MediaRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/test-openeditor', name: 'admin_test_openeditor_')]
#[IsGranted('ROLE_ADMIN')]
class TestOpenEditorController extends AbstractController
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private LanguageRepository $languageRepository,
        private MediaRepository $mediaRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/test-openeditor/index.html.twig', [
            'services' => $this->serviceRepository->findAll(),
            'languages' => $this->languageRepository->findActiveLanguages()
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $service = new Service();
        $languages = $this->languageRepository->findActiveLanguages();
        
        $form = $this->createForm(ServiceType::class, $service, [
            'is_edit' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sauvegarder le service principal
                $this->entityManager->persist($service);
                
                // Traiter les traductions
                foreach ($languages as $language) {
                    $translationFormName = 'translation_' . $language->getCode();
                    $translationData = $form->get($translationFormName)->getData();
                    
                    if ($translationData && (!empty($translationData->getTitle()) || !empty($translationData->getDescription()))) {
                        $translation = new ServiceTranslation();
                        $translation->setService($service);
                        $translation->setLanguage($language);
                        $translation->setTitle($translationData->getTitle() ?? '');
                        $translation->setDescription($translationData->getDescription() ?? '');
                        $translation->setMetaTitle($translationData->getMetaTitle() ?? '');
                        $translation->setMetaDescription($translationData->getMetaDescription() ?? '');
                        
                        $this->entityManager->persist($translation);
                        $service->addTranslation($translation);
                    }
                }

                $this->entityManager->flush();
                
                $this->addFlash('success', 'Service créé avec succès avec OpenEditor !');
                return $this->redirectToRoute('admin_test_openeditor_edit', ['id' => $service->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('admin/test-openeditor/new.html.twig', [
            'service' => $service,
            'form' => $form,
            'languages' => $languages
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service): Response
    {
        $languages = $this->languageRepository->findActiveLanguages();
        
        $form = $this->createForm(ServiceType::class, $service, [
            'is_edit' => true
        ]);

        // Pré-remplir les traductions existantes
        foreach ($languages as $language) {
            $translation = $service->getTranslation($language->getCode());
            $translationFormName = 'translation_' . $language->getCode();
            
            if ($translation) {
                $form->get($translationFormName)->setData($translation);
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Traiter les traductions
                foreach ($languages as $language) {
                    $translationFormName = 'translation_' . $language->getCode();
                    $translationData = $form->get($translationFormName)->getData();
                    
                    $existingTranslation = $service->getTranslation($language->getCode());
                    
                    if ($translationData && (!empty($translationData->getTitle()) || !empty($translationData->getDescription()))) {
                        if ($existingTranslation) {
                            // Mettre à jour la traduction existante
                            $existingTranslation->setTitle($translationData->getTitle() ?? '');
                            $existingTranslation->setDescription($translationData->getDescription() ?? '');
                            $existingTranslation->setMetaTitle($translationData->getMetaTitle() ?? '');
                            $existingTranslation->setMetaDescription($translationData->getMetaDescription() ?? '');
                            $existingTranslation->setUpdatedAt();
                        } else {
                            // Créer une nouvelle traduction
                            $translation = new ServiceTranslation();
                            $translation->setService($service);
                            $translation->setLanguage($language);
                            $translation->setTitle($translationData->getTitle() ?? '');
                            $translation->setDescription($translationData->getDescription() ?? '');
                            $translation->setMetaTitle($translationData->getMetaTitle() ?? '');
                            $translation->setMetaDescription($translationData->getMetaDescription() ?? '');
                            
                            $this->entityManager->persist($translation);
                            $service->addTranslation($translation);
                        }
                    } elseif ($existingTranslation) {
                        // Supprimer la traduction si elle est vide
                        $service->removeTranslation($existingTranslation);
                        $this->entityManager->remove($existingTranslation);
                    }
                }

                $this->entityManager->flush();
                
                $this->addFlash('success', 'Service mis à jour avec succès avec OpenEditor !');
                return $this->redirectToRoute('admin_test_openeditor_edit', ['id' => $service->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('admin/test-openeditor/edit.html.twig', [
            'service' => $service,
            'form' => $form,
            'languages' => $languages
        ]);
    }

    #[Route('/demo-openeditor', name: 'demo', methods: ['GET'])]
    public function demo(): Response
    {
        return $this->render('admin/test-openeditor/demo.html.twig');
    }
}