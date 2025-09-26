/**
 * OpenEditor - Éditeur de texte personnalisé pour Symfony
 * Auteur: MiniMax Agent
 * Version: 1.0.1 - Refactorisé avec gestion complète de la configuration
 */

(function($) {
    'use strict';

    // Plugin principal OpenEditor
    $.fn.openEditor = function(options) {
        // Configuration par défaut synchronisée avec PHP
        const defaults = {
            toolbar: {
                config: 'default', // default, minimal, full, custom
                groups: [
                    ['bold', 'italic', 'underline'],
                    ['link', 'unlink'],
                    ['image', 'media'],
                    ['ul', 'ol'],
                    ['undo', 'redo'],
                    ['source']
                ]
            },
            height: '300px',
            theme: 'default',
            mediaEnabled: true,
            mediaManagerUrl: '/open-editor/media-manager',
            uploadUrl: '/open-editor/media/upload',
            browseUrl: '/open-editor/media/browse',
            allowedTags: 'p,br,strong,em,u,a,img,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,code,pre',
            placeholder: 'Commencez à écrire...',
            autosave: false,
            autosaveInterval: 30000 // 30 secondes
        };

        return this.each(function() {
            const $textarea = $(this);
            
            // Vérifier si déjà initialisé
            if ($textarea.data('openEditor')) {
                return;
            }

            // Lire la configuration depuis les data-attributes
            const configFromData = readConfigFromDataAttributes($textarea);
            
            // Fusionner les configurations : defaults < options < data-attributes
            const settings = $.extend(true, {}, defaults, options, configFromData);

            // Valider et normaliser la configuration
            settings = validateAndNormalizeConfig(settings);

            try {
                // Créer l'instance de l'éditeur
                const editor = new OpenEditor($textarea, settings);
                
                // Stocker la référence
                $textarea.data('openEditor', editor);
                
                // Masquer le loading indicator
                $textarea.siblings('.open-editor-loading').hide();
                
            } catch (error) {
                console.error('OpenEditor: Erreur lors de l\'initialisation:', error);
                showErrorMessage($textarea, 'Erreur lors du chargement de l\'éditeur: ' + error.message);
            }
        });
    };

    /**
     * Lire la configuration depuis les data-attributes HTML
     */
    function readConfigFromDataAttributes($element) {
        const config = {};
        const dataAttrs = $element.data();

        // Mapping des attributs data-* vers les propriétés de configuration
        const mappings = {
            'toolbarConfig': 'toolbar.config',
            'toolbarGroups': 'toolbar.groups',
            'mediaEnabled': 'mediaEnabled',
            'mediaManagerUrl': 'mediaManagerUrl',
            'uploadUrl': 'uploadUrl',
            'browseUrl': 'browseUrl',
            'height': 'height',
            'theme': 'theme',
            'placeholder': 'placeholder',
            'autosave': 'autosave',
            'autosaveInterval': 'autosaveInterval',
            'allowedTags': 'allowedTags'
        };

        for (const [dataKey, configPath] of Object.entries(mappings)) {
            if (dataAttrs.hasOwnProperty(dataKey)) {
                let value = dataAttrs[dataKey];
                
                // Conversion des types
                if (value === 'true') value = true;
                else if (value === 'false') value = false;
                else if (typeof value === 'string' && value.match(/^\d+$/)) value = parseInt(value);
                else if (typeof value === 'string' && value.startsWith('[') || value.startsWith('{')) {
                    try {
                        value = JSON.parse(value);
                    } catch (e) {
                        console.warn('OpenEditor: Impossible de parser JSON pour', dataKey, ':', value);
                        continue;
                    }
                }
                
                // Définir la valeur dans la configuration avec notation par points
                setNestedValue(config, configPath, value);
            }
        }

        return config;
    }

    /**
     * Définir une valeur imbriquée avec notation par points
     */
    function setNestedValue(obj, path, value) {
        const keys = path.split('.');
        let current = obj;
        
        for (let i = 0; i < keys.length - 1; i++) {
            if (!current[keys[i]]) {
                current[keys[i]] = {};
            }
            current = current[keys[i]];
        }
        
        current[keys[keys.length - 1]] = value;
    }

    /**
     * Valider et normaliser la configuration
     */
    function validateAndNormalizeConfig(settings) {
        // Validation du thème
        const validThemes = ['default', 'dark', 'modern', 'minimal'];
        if (!validThemes.includes(settings.theme)) {
            console.warn('OpenEditor: Thème invalide:', settings.theme, '. Utilisation du thème par défaut.');
            settings.theme = 'default';
        }

        // Validation de la hauteur
        if (typeof settings.height === 'string' && !settings.height.match(/^\d+(px|em|%|rem|vh)$/)) {
            console.warn('OpenEditor: Hauteur invalide:', settings.height, '. Utilisation de la hauteur par défaut.');
            settings.height = '300px';
        }

        // Validation de l'intervalle d'autosave
        if (typeof settings.autosaveInterval !== 'number' || settings.autosaveInterval <= 0) {
            console.warn('OpenEditor: Intervalle d\'autosave invalide:', settings.autosaveInterval);
            settings.autosaveInterval = 30000;
        }

        // Validation des URLs
        const urlFields = ['mediaManagerUrl', 'uploadUrl', 'browseUrl'];
        urlFields.forEach(field => {
            if (settings[field] && typeof settings[field] === 'string') {
                if (!isValidUrl(settings[field])) {
                    console.warn('OpenEditor: URL invalide pour', field, ':', settings[field]);
                    settings[field] = '';
                }
            }
        });

        return settings;
    }

    /**
     * Valider si une chaîne est une URL valide
     */
    function isValidUrl(string) {
        try {
            // Vérifier si c'est une URL absolue valide
            new URL(string);
            return true;
        } catch {
            // Pour les chemins relatifs, vérifier qu'ils sont sécurisés
            if (string.startsWith('/')) {
                // Rejeter les chemins avec traversal
                if (string.includes('..') || string.includes('//')) {
                    return false;
                }
                // Vérifier le format du chemin
                return /^\/[a-zA-Z0-9\/_-]*$/.test(string);
            }
            return false;
        }
    }

    /**
     * Afficher un message d'erreur
     */
    function showErrorMessage($element, message) {
        const $error = $('<div class="alert alert-danger open-editor-error">' +
            '<strong>Erreur OpenEditor:</strong> ' + message +
            '</div>');
        $element.before($error);
        
        // Masquer le message d'erreur après 10 secondes
        setTimeout(() => $error.fadeOut(), 10000);
    }

    // Classe principale OpenEditor
    class OpenEditor {
        constructor($textarea, settings) {
            this.$textarea = $textarea;
            this.settings = settings;
            this.id = 'open-editor-' + Math.random().toString(36).substr(2, 9);
            this.content = $textarea.val();
            this.history = [];
            this.historyIndex = -1;
            this.currentRange = null;
            
            this.init();
        }

        init() {
            this.createStructure();
            this.createToolbar();
            this.createEditor();
            this.bindEvents();
            this.loadContent();
            
            // Masquer le textarea original
            this.$textarea.hide();
            
            // Auto-save si activé
            if (this.settings.autosave) {
                this.initAutosave();
            }

            console.log('OpenEditor initialisé:', this.id);
        }

        createStructure() {
            this.$container = $(`
                <div class="open-editor-container ${this.settings.theme}" id="${this.id}">
                    <div class="open-editor-toolbar"></div>
                    <div class="open-editor-content" contenteditable="true"></div>
                    <div class="open-editor-status"></div>
                </div>
            `);
            
            this.$container.insertAfter(this.$textarea);
            this.$toolbar = this.$container.find('.open-editor-toolbar');
            this.$editor = this.$container.find('.open-editor-content');
            this.$status = this.$container.find('.open-editor-status');
            
            // Appliquer la hauteur
            this.$editor.css('min-height', this.settings.height);
        }

        createToolbar() {
            const toolbarHtml = this.generateToolbarHtml();
            this.$toolbar.html(toolbarHtml);
        }

        generateToolbarHtml() {
            let html = '';
            
            this.settings.toolbar.groups.forEach((group, groupIndex) => {
                if (groupIndex > 0) {
                    html += '<span class="toolbar-separator">|</span>';
                }
                
                group.forEach(button => {
                    html += this.createToolbarButton(button);
                });
            });
            
            return html;
        }

        createToolbarButton(type) {
            const buttons = {
                bold: { icon: '★', title: 'Gras (Ctrl+B)', cmd: 'bold' },
                italic: { icon: '𝐼', title: 'Italique (Ctrl+I)', cmd: 'italic' },
                underline: { icon: '̲U', title: 'Souligné (Ctrl+U)', cmd: 'underline' },
                link: { icon: '🔗', title: 'Insérer un lien', cmd: 'createLink' },
                unlink: { icon: '🔗⛔', title: 'Supprimer le lien', cmd: 'unlink' },
                image: { icon: '🖼️', title: 'Insérer une image', cmd: 'insertImage' },
                media: { icon: '🖾', title: 'Gestionnaire de médias', cmd: 'openMediaManager' },
                ul: { icon: '•', title: 'Liste à puces', cmd: 'insertUnorderedList' },
                ol: { icon: '1.', title: 'Liste numérotée', cmd: 'insertOrderedList' },
                undo: { icon: '↶', title: 'Annuler (Ctrl+Z)', cmd: 'undo' },
                redo: { icon: '↷', title: 'Rétablir (Ctrl+Y)', cmd: 'redo' },
                source: { icon: '</>', title: 'Mode source', cmd: 'toggleSource' }
            };

            const button = buttons[type];
            if (!button) return '';

            return `<button type="button" class="toolbar-btn" data-cmd="${button.cmd}" title="${button.title}">
                        <span class="btn-icon">${button.icon}</span>
                    </button>`;
        }

        createEditor() {
            this.$editor.attr('placeholder', this.settings.placeholder);
        }

        bindEvents() {
            // Événements de la toolbar
            this.$toolbar.on('click', '.toolbar-btn', (e) => {
                e.preventDefault();
                const cmd = $(e.currentTarget).data('cmd');
                this.executeCommand(cmd);
            });

            // Événements de l'éditeur
            this.$editor.on('input', () => {
                this.onContentChange();
            });

            this.$editor.on('keydown', (e) => {
                this.handleKeydown(e);
            });

            this.$editor.on('mouseup keyup', () => {
                this.saveSelection();
                this.updateToolbarState();
            });

            // Événements de paste
            this.$editor.on('paste', (e) => {
                this.handlePaste(e);
            });
        }

        executeCommand(cmd) {
            this.restoreSelection();
            
            switch(cmd) {
                case 'openMediaManager':
                    this.openMediaManager();
                    break;
                case 'createLink':
                    this.insertLink();
                    break;
                case 'insertImage':
                    this.insertImage();
                    break;
                case 'toggleSource':
                    this.toggleSourceMode();
                    break;
                case 'undo':
                    this.undo();
                    break;
                case 'redo':
                    this.redo();
                    break;
                default:
                    document.execCommand(cmd, false, null);
            }
            
            this.$editor.focus();
            this.onContentChange();
        }

        openMediaManager() {
            // Créer et ouvrir le gestionnaire de médias
            if (window.OpenEditorMediaManager) {
                const mediaManager = new window.OpenEditorMediaManager(this.settings);
                mediaManager.open((selectedMedia) => {
                    this.insertMedia(selectedMedia);
                });
            } else {
                alert('Gestionnaire de médias non disponible');
            }
        }

        insertMedia(media) {
            this.restoreSelection();
            
            if (media.type === 'image') {
                const img = `<img src="${media.url}" alt="${media.name}" class="inserted-media">`;
                document.execCommand('insertHTML', false, img);
            } else {
                const link = `<a href="${media.url}" target="_blank">${media.name}</a>`;
                document.execCommand('insertHTML', false, link);
            }
            
            this.onContentChange();
        }

        insertLink() {
            const url = prompt('Entrez l\'URL du lien:');
            if (url) {
                document.execCommand('createLink', false, url);
            }
        }

        insertImage() {
            const url = prompt('Entrez l\'URL de l\'image:');
            if (url) {
                const img = `<img src="${url}" alt="Image" class="inserted-image">`;
                document.execCommand('insertHTML', false, img);
            }
        }

        toggleSourceMode() {
            // Implémentation du mode source HTML
            console.log('Mode source - à implémenter');
        }

        handleKeydown(e) {
            // Raccourcis clavier
            if (e.ctrlKey || e.metaKey) {
                switch(e.keyCode) {
                    case 66: // Ctrl+B
                        e.preventDefault();
                        this.executeCommand('bold');
                        break;
                    case 73: // Ctrl+I
                        e.preventDefault();
                        this.executeCommand('italic');
                        break;
                    case 85: // Ctrl+U
                        e.preventDefault();
                        this.executeCommand('underline');
                        break;
                    case 90: // Ctrl+Z
                        e.preventDefault();
                        this.executeCommand('undo');
                        break;
                    case 89: // Ctrl+Y
                        e.preventDefault();
                        this.executeCommand('redo');
                        break;
                }
            }
        }

        handlePaste(e) {
            e.preventDefault();
            
            const clipboardData = e.originalEvent.clipboardData || window.clipboardData;
            const pastedData = clipboardData.getData('text/html') || clipboardData.getData('text/plain');
            
            // Nettoyer le contenu collé
            const cleanData = this.cleanPastedContent(pastedData);
            document.execCommand('insertHTML', false, cleanData);
            
            this.onContentChange();
        }

        cleanPastedContent(content) {
            // Supprimer les balises non autorisées
            const allowedTags = this.settings.allowedTags.split(',');
            // Implémentation basique - à améliorer
            return content;
        }

        saveSelection() {
            if (window.getSelection) {
                const sel = window.getSelection();
                if (sel.rangeCount > 0) {
                    this.currentRange = sel.getRangeAt(0);
                }
            }
        }

        restoreSelection() {
            if (this.currentRange) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(this.currentRange);
            }
        }

        updateToolbarState() {
            // Mettre à jour l'état des boutons de la toolbar
            this.$toolbar.find('.toolbar-btn').removeClass('active');
            
            const commands = ['bold', 'italic', 'underline'];
            commands.forEach(cmd => {
                if (document.queryCommandState(cmd)) {
                    this.$toolbar.find(`[data-cmd="${cmd}"]`).addClass('active');
                }
            });
        }

        onContentChange() {
            // Synchroniser avec le textarea original
            const content = this.$editor.html();
            this.$textarea.val(content).trigger('change');
            
            // Ajouter à l'historique
            this.addToHistory(content);
            
            // Mettre à jour le statut
            this.updateStatus();
        }

        addToHistory(content) {
            // Ajouter seulement si différent du dernier élément
            if (this.history.length === 0 || this.history[this.history.length - 1] !== content) {
                this.history.push(content);
                this.historyIndex = this.history.length - 1;
                
                // Limiter l'historique à 50 éléments
                if (this.history.length > 50) {
                    this.history.shift();
                    this.historyIndex--;
                }
            }
        }

        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                const content = this.history[this.historyIndex];
                this.$editor.html(content);
                this.$textarea.val(content);
            }
        }

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                const content = this.history[this.historyIndex];
                this.$editor.html(content);
                this.$textarea.val(content);
            }
        }

        loadContent() {
            const content = this.$textarea.val();
            this.$editor.html(content);
            this.addToHistory(content);
        }

        updateStatus() {
            const content = this.$editor.text();
            const wordCount = content.trim().split(/\s+/).length;
            const charCount = content.length;
            
            this.$status.html(`Mots: ${wordCount} | Caractères: ${charCount}`);
        }

        initAutosave() {
            setInterval(() => {
                // Implémentation de la sauvegarde automatique
                console.log('Sauvegarde automatique...');
            }, this.settings.autosaveInterval);
        }

        // Méthodes publiques
        getContent() {
            return this.$editor.html();
        }

        setContent(content) {
            this.$editor.html(content);
            this.$textarea.val(content);
            this.addToHistory(content);
        }

        destroy() {
            this.$container.remove();
            this.$textarea.show().removeData('openEditor');
        }
    }

    // Initialisation automatique avec gestion d'erreur améliorée
    $(document).ready(function() {
        // Afficher les loading indicators
        $('.open-editor-field').siblings('.open-editor-loading').show();
        
        // Attendre que les ressources CSS soient chargées
        $(window).on('load', function() {
            // Initialiser tous les champs OpenEditor avec classe .open-editor-field
            $('.open-editor-field').each(function() {
                const $field = $(this);
                
                // Vérifier si le field a déjà été initialisé
                if (!$field.data('openEditor')) {
                    try {
                        $field.openEditor();
                    } catch (error) {
                        console.error('OpenEditor: Erreur d\'initialisation automatique:', error);
                        showErrorMessage($field, 'Initialisation automatique échouée');
                    }
                }
            });
        });
    });

    // Initialisation manuelle pour les champs dynamiques
    $(document).on('DOMNodeInserted', function(e) {
        const $target = $(e.target);
        if ($target.hasClass('open-editor-field') && !$target.data('openEditor')) {
            setTimeout(() => {
                try {
                    $target.openEditor();
                } catch (error) {
                    console.error('OpenEditor: Erreur d\'initialisation dynamique:', error);
                }
            }, 100);
        }
    });

    // API publique pour l'accès depuis l'extérieur
    window.OpenEditor = {
        // Initialiser manuellement un champ
        init: function(selector, options) {
            $(selector).openEditor(options);
        },
        
        // Obtenir l'instance d'un éditeur
        getInstance: function(selector) {
            return $(selector).first().data('openEditor');
        },
        
        // Détruire un éditeur
        destroy: function(selector) {
            const $field = $(selector);
            const editor = $field.data('openEditor');
            if (editor && typeof editor.destroy === 'function') {
                editor.destroy();
            }
        },
        
        // Obtenir/définir le contenu
        getContent: function(selector) {
            const editor = this.getInstance(selector);
            return editor ? editor.getContent() : '';
        },
        
        setContent: function(selector, content) {
            const editor = this.getInstance(selector);
            if (editor && typeof editor.setContent === 'function') {
                editor.setContent(content);
            }
        }
    };

})(jQuery);
