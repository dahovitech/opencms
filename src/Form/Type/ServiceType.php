<?php

namespace App\Form\Type;

use App\Entity\Language;
use App\Entity\Media;
use App\Entity\Service;
use App\Repository\LanguageRepository;
use App\Repository\MediaRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ServiceType - FormType principal pour la gestion des services avec OpenEditor
 * 
 * ARCHITECTURE DES TRADUCTIONS :
 * - Les traductions sont gérées via des sous-formulaires dynamiques nommés 'translation_{code_langue}'
 * - Chaque sous-formulaire utilise ServiceTranslationType qui inclut OpenEditor pour la description
 * - Les traductions ne sont pas mappées directement à l'entité Service (gestion manuelle dans le contrôleur)
 * - Cette approche permet une validation granulaire par langue tout en conservant la flexibilité
 * 
 * GESTION DES ERREURS :
 * - Validation des sous-formulaires de traductions via ServiceTranslationType
 * - Gestion des erreurs OpenEditor intégrée dans le theme de formulaire
 * - Validation des relations Service-Language dans le contrôleur
 * 
 * Auteur: MiniMax Agent
 * Intégré dans OpenCMS - Version refactorisée avec amélioration de la robustesse
 */
class ServiceType extends AbstractType
{
    public function __construct(
        private LanguageRepository $languageRepository,
        private MediaRepository $mediaRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Sera généré automatiquement si vide',
                    'class' => 'form-control'
                ],
                'help' => 'Laissez vide pour génération automatique basée sur le titre'
            ])
            ->add('image', EntityType::class, [
                'class' => Media::class,
                'choice_label' => 'filename',
                'placeholder' => 'Sélectionner une image...',
                'required' => false,
                'label' => 'Image du service',
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Image représentative du service'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Service actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Décochez pour masquer le service du site public'
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordre de tri',
                'required' => false,
                'data' => 0,
                'attr' => [
                    'min' => 0,
                    'class' => 'form-control'
                ],
                'help' => 'Ordre d\'affichage du service (0 = premier)'
            ]);

        // Ajouter les champs de traduction pour chaque langue active
        $languages = $this->languageRepository->findActiveLanguages();
        
        foreach ($languages as $language) {
            $builder->add('translation_' . $language->getCode(), ServiceTranslationType::class, [
                'label' => 'Contenu en ' . $language->getName(),
                'required' => false,
                'mapped' => false, // Nous gérerons manuellement les traductions
                'attr' => [
                    'class' => 'translation-form',
                    'data-language' => $language->getCode()
                ]
            ]);
        }

        $builder->add('save', SubmitType::class, [
            'label' => $options['is_edit'] ? 'Mettre à jour' : 'Créer le service',
            'attr' => [
                'class' => 'btn btn-primary'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}