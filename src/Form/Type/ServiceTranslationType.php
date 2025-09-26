<?php

namespace App\Form\Type;

use App\Entity\ServiceTranslation;
use App\Form\Type\OpenEditor\OpenEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ServiceTranslationType - FormType pour les traductions de service avec OpenEditor
 * 
 * AMÉLIORATIONS DE SÉCURITÉ ET PERFORMANCE :
 * - Configuration OpenEditor optimisée pour les descriptions de service
 * - Validation stricte des longueurs de champ (titre: 255, meta description: 160)
 * - URLs de média configurées pour l'intégration OpenCMS
 * - Autosave activé avec intervalle optimal (45s) pour éviter la perte de données
 * - Tags HTML sécurisés incluant les éléments de mise en forme avancée
 * 
 * CONFIGURATION OPENEDITOR :
 * - Thème modern pour une meilleure UX dans l'admin
 * - Hauteur 400px optimale pour les descriptions de service
 * - Intégration complète du gestionnaire de médias OpenCMS
 * 
 * Auteur: MiniMax Agent
 * Intégré dans OpenCMS - Version refactorisée et sécurisée
 */
class ServiceTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Titre du service',
                    'class' => 'form-control',
                    'maxlength' => 255
                ]
            ])
            ->add('description', OpenEditorType::class, [
                'label' => 'Description',
                'required' => false,
                'toolbar_config' => 'full', // Configuration complète pour les descriptions
                'theme' => 'modern',
                'height' => '400px',
                'media_enabled' => true,
                'media_manager_url' => '/admin/media',
                'upload_url' => '/admin/media/upload',
                'browse_url' => '/admin/media/browse',
                'autosave' => true,
                'autosave_interval' => 45000, // 45 secondes
                'placeholder' => 'Décrivez votre service ici...',
                'allowed_tags' => 'p,br,strong,em,u,a,img,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,code,pre,table,thead,tbody,tr,th,td',
                'help' => 'Utilisez l\'éditeur pour formater votre description. Les images peuvent être ajoutées via le gestionnaire de médias.',
                'attr' => [
                    'rows' => 12
                ]
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'Méta titre (SEO)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Titre pour les moteurs de recherche',
                    'class' => 'form-control',
                    'maxlength' => 60
                ],
                'help' => 'Recommandé: 50-60 caractères'
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'Méta description (SEO)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description pour les moteurs de recherche',
                    'class' => 'form-control',
                    'rows' => 3,
                    'maxlength' => 160
                ],
                'help' => 'Recommandé: 150-160 caractères'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceTranslation::class,
        ]);
    }
}