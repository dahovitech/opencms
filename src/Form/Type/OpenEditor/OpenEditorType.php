<?php

namespace App\Form\Type\OpenEditor;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Exception\InvalidConfigurationException;

/**
 * OpenEditorType - FormType personnalisé pour l'éditeur OpenEditor
 * 
 * Auteur: MiniMax Agent
 * Intégré dans le projet OpenCMS
 * 
 * Ce FormType étend TextareaType pour transformer automatiquement
 * les textareas en éditeurs WYSIWYG OpenEditor avec une gestion
 * robuste des configurations et une validation complète.
 */
class OpenEditorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Construire la configuration finale
        $config = $this->buildEditorConfig($options);
        
        // Valider la configuration
        $this->validateConfig($config);
        
        // Stocker la configuration validée
        $builder->setAttribute('open_editor_config', $config);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        
        // Ajouter les classes CSS nécessaires
        $cssClasses = array_filter([
            $view->vars['attr']['class'] ?? '',
            'open-editor-field',
            'open-editor-theme-' . $options['theme']
        ]);
        $view->vars['attr']['class'] = implode(' ', $cssClasses);
        
        // Récupérer et passer la configuration à la vue
        $config = $form->getConfig()->getAttribute('open_editor_config');
        $view->vars['open_editor_config'] = $config;
        
        // Convertir la configuration en data-attributes pour JavaScript
        $this->addDataAttributes($view, $config);
    }

    /**
     * Construire la configuration de l'éditeur
     */
    private function buildEditorConfig(array $options): array
    {
        $config = [
            'toolbar_config' => $options['toolbar_config'],
            'media_enabled' => $options['media_enabled'],
            'height' => $options['height'],
            'theme' => $options['theme'],
            'placeholder' => $options['placeholder'],
            'autosave' => $options['autosave'],
            'autosave_interval' => $options['autosave_interval'],
            'allowed_tags' => $options['allowed_tags'],
            'media_manager_url' => $options['media_manager_url'],
            'upload_url' => $options['upload_url'],
            'browse_url' => $options['browse_url'],
        ];

        // Si toolbar_config est 'custom', inclure toolbar_groups
        if ($options['toolbar_config'] === 'custom') {
            $config['toolbar_groups'] = $options['toolbar_groups'];
        }

        return $config;
    }

    /**
     * Valider la configuration
     */
    private function validateConfig(array $config): void
    {
        // Validation des URLs
        $urlFields = ['media_manager_url', 'upload_url', 'browse_url'];
        foreach ($urlFields as $field) {
            if (!empty($config[$field]) && !$this->isValidUrl($config[$field])) {
                throw new InvalidConfigurationException(
                    sprintf('L\'option "%s" doit être une URL valide.', $field)
                );
            }
        }

        // Validation des entiers positifs
        if ($config['autosave_interval'] <= 0) {
            throw new InvalidConfigurationException(
                'L\'option "autosave_interval" doit être un entier positif.'
            );
        }

        // Validation de la hauteur (doit contenir px, em, %, etc.)
        if (!preg_match('/^\d+(px|em|%|rem|vh)$/', $config['height'])) {
            throw new InvalidConfigurationException(
                'L\'option "height" doit être une valeur CSS valide (ex: "300px", "20em").'
            );
        }
    }

    /**
     * Ajouter les data-attributes à la vue
     */
    private function addDataAttributes(FormView $view, array $config): void
    {
        foreach ($config as $key => $value) {
            $dataAttribute = 'data-' . str_replace('_', '-', $key);
            
            try {
                if (is_bool($value)) {
                    $view->vars['attr'][$dataAttribute] = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $view->vars['attr'][$dataAttribute] = json_encode($value, JSON_THROW_ON_ERROR);
                } elseif (is_null($value)) {
                    $view->vars['attr'][$dataAttribute] = '';
                } else {
                    $view->vars['attr'][$dataAttribute] = (string) $value;
                }
            } catch (\JsonException $e) {
                throw new InvalidConfigurationException(
                    sprintf('Impossible d\'encoder la configuration "%s" en JSON : %s', $key, $e->getMessage())
                );
            }
        }
    }

    /**
     * Valider si une chaîne est une URL valide
     */
    private function isValidUrl(string $url): bool
    {
        try {
            // Vérifier si c'est une URL absolue valide
            new \Symfony\Component\Routing\Generator\UrlGenerator(
                new \Symfony\Component\Routing\RouteCollection(),
                new \Symfony\Component\Routing\RequestContext()
            );
            filter_var($url, FILTER_VALIDATE_URL);
            return true;
        } catch (\Exception $e) {
            // Pour les chemins relatifs, vérifier qu'ils sont sécurisés
            if (str_starts_with($url, '/')) {
                // Rejeter les chemins avec traversal
                if (str_contains($url, '..') || str_contains($url, '//')) {
                    return false;
                }
                // Vérifier le format du chemin
                return preg_match('/^\/[a-zA-Z0-9\/_-]*$/', $url) === 1;
            }
            return false;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configuration de la toolbar
            'toolbar_config' => 'default', // default, minimal, full, custom
            'toolbar_groups' => [
                ['bold', 'italic', 'underline'],
                ['link', 'unlink'],
                ['image', 'media'],
                ['ul', 'ol'],
                ['undo', 'redo'],
                ['source']
            ],
            
            // Gestionnaire de médias - adaptés pour OpenCMS
            'media_enabled' => true,
            'media_manager_url' => '/admin/media',
            'upload_url' => '/admin/media/upload',
            'browse_url' => '/admin/media/browse',
            
            // Apparence
            'height' => '300px',
            'theme' => 'default', // default, dark, modern, minimal
            'placeholder' => 'Commencez à écrire...',
            
            // Fonctionnalités
            'autosave' => false,
            'autosave_interval' => 30000, // en millisecondes
            'allowed_tags' => 'p,br,strong,em,u,a,img,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,code,pre',
            
            // Attributs HTML par défaut
            'attr' => [
                'class' => 'open-editor-field',
                'rows' => 10
            ]
        ]);

        // Définition des types autorisés avec validation stricte
        $resolver->setAllowedTypes('toolbar_config', 'string');
        $resolver->setAllowedTypes('toolbar_groups', 'array');
        $resolver->setAllowedTypes('media_enabled', 'bool');
        $resolver->setAllowedTypes('media_manager_url', 'string');
        $resolver->setAllowedTypes('upload_url', 'string');
        $resolver->setAllowedTypes('browse_url', 'string');
        $resolver->setAllowedTypes('height', 'string');
        $resolver->setAllowedTypes('theme', 'string');
        $resolver->setAllowedTypes('placeholder', 'string');
        $resolver->setAllowedTypes('autosave', 'bool');
        $resolver->setAllowedTypes('autosave_interval', 'int');
        $resolver->setAllowedTypes('allowed_tags', 'string');
        
        // Définition des valeurs autorisées
        $resolver->setAllowedValues('toolbar_config', ['default', 'minimal', 'full', 'custom']);
        $resolver->setAllowedValues('theme', ['default', 'dark', 'modern', 'minimal']);
        
        // Validation personnalisée pour autosave_interval
        $resolver->setAllowedValues('autosave_interval', function ($value) {
            return is_int($value) && $value > 0;
        });

        // Normalisation des données
        $resolver->setNormalizer('toolbar_groups', function ($options, $value) {
            // Si toolbar_config n'est pas 'custom', ignorer toolbar_groups
            if ($options['toolbar_config'] !== 'custom') {
                return [];
            }
            
            // Valider la structure de toolbar_groups
            if (!is_array($value)) {
                throw new InvalidConfigurationException(
                    'L\'option "toolbar_groups" doit être un tableau.'
                );
            }
            
            foreach ($value as $group) {
                if (!is_array($group)) {
                    throw new InvalidConfigurationException(
                        'Chaque groupe dans "toolbar_groups" doit être un tableau.'
                    );
                }
            }
            
            return $value;
        });

        $resolver->setNormalizer('allowed_tags', function ($options, $value) {
            // Nettoyer et valider les tags autorisés
            $tags = array_map('trim', explode(',', $value));
            $validTags = [];
            
            foreach ($tags as $tag) {
                if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $tag)) {
                    $validTags[] = $tag;
                }
            }
            
            return implode(',', $validTags);
        });
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'open_editor';
    }

    /**
     * Obtenir les presets de toolbar disponibles
     */
    public static function getToolbarPresets(): array
    {
        return [
            'minimal' => [
                ['bold', 'italic'],
                ['undo', 'redo']
            ],
            'default' => [
                ['bold', 'italic', 'underline'],
                ['link', 'unlink'],
                ['image', 'media'],
                ['ul', 'ol'],
                ['undo', 'redo'],
                ['source']
            ],
            'full' => [
                ['bold', 'italic', 'underline', 'strikethrough'],
                ['h1', 'h2', 'h3'],
                ['link', 'unlink'],
                ['image', 'media', 'table'],
                ['ul', 'ol', 'blockquote'],
                ['alignleft', 'aligncenter', 'alignright'],
                ['undo', 'redo'],
                ['source', 'fullscreen']
            ]
        ];
    }
}