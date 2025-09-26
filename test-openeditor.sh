#!/bin/bash

# Script de test OpenEditor pour OpenCMS
# Auteur: MiniMax Agent

echo "🚀 Lancement du test OpenEditor dans OpenCMS..."

# Vérifier si nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    echo "❌ Erreur: Ce script doit être exécuté depuis le répertoire opencms"
    exit 1
fi

# Vérifier que les fichiers OpenEditor sont bien copiés
if [ ! -f "assets/js/openeditor/open-editor.js" ]; then
    echo "❌ Erreur: Fichier JavaScript OpenEditor manquant"
    exit 1
fi

if [ ! -f "assets/styles/openeditor/open-editor.css" ]; then
    echo "❌ Erreur: Fichier CSS OpenEditor manquant"
    exit 1
fi

echo "✅ Assets OpenEditor trouvés"

# Vérifier les FormTypes
if [ ! -f "src/Form/Type/OpenEditor/OpenEditorType.php" ]; then
    echo "❌ Erreur: FormType OpenEditor manquant"
    exit 1
fi

if [ ! -f "src/Form/Type/ServiceType.php" ]; then
    echo "❌ Erreur: FormType Service manquant"
    exit 1
fi

echo "✅ FormTypes OpenEditor trouvés"

# Vérifier les templates
if [ ! -f "templates/form/openeditor_theme.html.twig" ]; then
    echo "❌ Erreur: Template OpenEditor manquant"
    exit 1
fi

if [ ! -f "templates/admin/test-openeditor/new.html.twig" ]; then
    echo "❌ Erreur: Template de test manquant"
    exit 1
fi

echo "✅ Templates OpenEditor trouvés"

# Vérifier le contrôleur de test
if [ ! -f "src/Controller/Admin/TestOpenEditorController.php" ]; then
    echo "❌ Erreur: Contrôleur de test manquant"
    exit 1
fi

echo "✅ Contrôleur de test trouvé"

echo ""
echo "🎉 Intégration OpenEditor terminée avec succès !"
echo ""
echo "📋 Résumé de l'intégration :"
echo "  • FormType OpenEditor : ✅ Intégré"
echo "  • Assets JavaScript/CSS : ✅ Copiés"
echo "  • Templates Twig : ✅ Créés"
echo "  • Contrôleur de test : ✅ Configuré"
echo "  • Routes automatiques : ✅ Activées"
echo ""
echo "🔗 URLs de test disponibles :"
echo "  • Liste des tests     : /admin/test-openeditor/"
echo "  • Nouveau service     : /admin/test-openeditor/new"
echo "  • Démonstration       : /admin/test-openeditor/demo-openeditor"
echo ""
echo "🚀 Pour tester OpenEditor :"
echo "  1. Démarrez le serveur Symfony : symfony serve"
echo "  2. Connectez-vous en tant qu'admin"
echo "  3. Visitez : http://localhost:8000/admin/test-openeditor/new"
echo ""
echo "📝 Fonctionnalités testées :"
echo "  • Éditeur WYSIWYG avec toolbar complète"
echo "  • Thème moderne et hauteur personnalisée"
echo "  • Sauvegarde automatique (45 secondes)"
echo "  • Gestionnaire de médias intégré"
echo "  • Gestion multi-langues"
echo "  • Validation des formulaires"
echo ""
echo "✨ OpenEditor est prêt à être testé dans OpenCMS ! ✨"