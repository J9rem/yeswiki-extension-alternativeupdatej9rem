<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
  // Feature UUID : auj9-autoupdate-system
  'ALTERNATIVEUPDATE_ABSENT' => 'Non installé',
  'ALTERNATIVEUPDATE_ACTIVATE' => 'Activer',
  'ALTERNATIVEUPDATE_ACTIVATED' => '{name} a été activée',
  'ALTERNATIVEUPDATE_ACTIVATING' => 'Activation de {name}',
  'ALTERNATIVEUPDATE_ACTIVATION_ERROR' => 'Activation/désactivation impossible',
  'ALTERNATIVEUPDATE_AVAILABLE_REVISION' => 'Version disponible',
  'ALTERNATIVEUPDATE_DATA_DELETED' => '{name} a été supprimée',
  'ALTERNATIVEUPDATE_DATA_LOADED' => "Données chargées",
  'ALTERNATIVEUPDATE_DEACTIVATE' => 'Désactiver',
  'ALTERNATIVEUPDATE_DEACTIVATED' => '{name} a été désactivée',
  'ALTERNATIVEUPDATE_DEACTIVATING' => 'Désactivation de {name}',
  'ALTERNATIVEUPDATE_DELETE' => 'Supprimer',
  'ALTERNATIVEUPDATE_DELETE_ERROR' => 'Suppression impossible',
  'ALTERNATIVEUPDATE_DELETING' => 'Suppresion de {name}',
  'ALTERNATIVEUPDATE_INSTALL' => 'Installer',
  'ALTERNATIVEUPDATE_INSTALLED_REVISION' => 'Version installée',
  'ALTERNATIVEUPDATE_INSTALLING' => 'Installation de {name} ({version})',
  'ALTERNATIVEUPDATE_INSTALL_ERROR' => 'Erreur lors de l\'installation de {name} ({version})',
  'ALTERNATIVEUPDATE_INSTALL_WARNING' => '{name} ({version}) ne peut être installé',
  'ALTERNATIVEUPDATE_LOADING_DATA' => "Chargement des données",
  'ALTERNATIVEUPDATE_LOADING_DATA_ERROR' => "Impossible de charger les données",
  'ALTERNATIVEUPDATE_LOADING_DATA_ERROR_PART' => "Impossible de charger une partie des données",
  'ALTERNATIVEUPDATE_LOCAL_THEMES' => 'Thèmes locaux',
  'ALTERNATIVEUPDATE_LOCAL_TOOLS' => 'Extensions locales',
  'ALTERNATIVEUPDATE_NAME' => 'Nom',
  'ALTERNATIVEUPDATE_NO_OFFICIAL_THEMES' => 'Thèmes non officiels ({keyName})',
  'ALTERNATIVEUPDATE_NO_OFFICIAL_TOOLS' => 'Tools/extensions non officielles ({keyName})',
  'ALTERNATIVEUPDATE_ONE_TEXT_UPDATE' => 'Vous avez 1 ext. à mettre à jour',
  'ALTERNATIVEUPDATE_PASSWORD_CHECK' => "Vérification du mot de passe",
  'ALTERNATIVEUPDATE_PASSWORD_CHECK_ERROR' => "Impossible de vérifier votre mot de passe",
  'ALTERNATIVEUPDATE_REINSTALL' => 'Réinstaller',
  'ALTERNATIVEUPDATE_SEVERAL_EXTS_UPDATE' => 'Vous avez {nbMaj} ext. à mettre à jour',
  'ALTERNATIVEUPDATE_TOKEN_ERROR' => "getListOfPackages ne peut être appelé sans token",
  'ALTERNATIVEUPDATE_UPDATE' => 'Mettre à jour',
  'ALTERNATIVEUPDATE_WIKI_IN_HIBERNATION' => 'Action désactivée car ce wiki est en lecture seule. Veuillez contacter l\'administrateur pour le réactiver.',
  'ALTERNATIVEUPDATE_WRONG_PASSWORD' => "Mauvais mot de passe",

  // custom sendmail
  // Feature UUID : auj9-custom-sendmail
  'ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_LABEL' => "E-mail avec choix si envoyer",
  'ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_ASK_FOR_CURRENT_SAVE' => "Demander pour envoyer un message à chaque sauvegarde",
  'ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_ASK_FOR_CURRENT_SAVE_HINT' => "Concerne l'envoi d'un e-mail lors de la sauvegarde courante de la fiche",
  'ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_LABEL_FOR_OPTIONS' => "Texte pour l'option d'envoi",
  'ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_LABEL_FOR_OPTIONS_VALUE' => "Envoyer un e-mail cette fois-ci ?",
  'ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_HINT' => 'Ce champ vient compléter le champ e-mail de la fiche mais ne le remplace pas.<br/>'.
    'Il permet à l\'utilsateur de choisir s\'il veut recevoir une copie de sa fiche à chaque modification (alors que sinon ce comportement est figée par le formulaire).',
  // send mail selector
  // Feature UUID : auj9-send-mail-selector-field
  'ALTERNATIVEUPDATE_SENDMAIL_SELECTOR_LABEL' => "Sélecteur d'e-mails",
  'ALTERNATIVEUPDATE_SENDMAIL_SELECTOR_HINT' => 'Ce champ peut remplacer le champ e-mail de la fiche ou se rajouter à côté.<br/>'.
    'Il permet de choisir l\'adresse e-mail parmi une liste. Ceci peut être pratique s\'il faut envoyer une '.
    'copie de la fiche à chaque modification à une adresse différente selon la zone géographique concernée.',
  // fields
  // Feature UUID : auj9-send-mail-selector-field
  // Feature UUID : auj9-custom-sendmail
  'ALTERNATIVEUPDATE_FIELD_FORM' => 'Champ fourni par l\'extension "alternativeupdatej9rem"',
  'ALTERNATIVEUPDATE_FIELD_LINKEDLABEL' => 'Champ e-mail dans la fiche courante',
  'ALTERNATIVEUPDATE_FIELD_LINKEDLABEL_HINT' => 'Laisser vide pour ne pas utiliser la fiche courante',
  // javascripts/fields/urlfield.js
  // Feature UUID : auj9-video-field
  'ALTERNATIVEUPDATE_VIDEO_MAXHEIGHT_LABEL' => 'Hauteur maximal de la vidéo',
  'ALTERNATIVEUPDATE_VIDEO_MAXWIDTH_LABEL' => 'Largeur maximal de la vidéo',
  'ALTERNATIVEUPDATE_VIDEO_MAX_HINT' => 'Uniquement un nombre positif de pixels sans l\'unité ; ex: 200',
  'ALTERNATIVEUPDATE_VIDEO_RATIO_LABEL' => 'Forme de l\'affichage',
  'ALTERNATIVEUPDATE_VIDEO_POSITION_LABEL' => 'Position de la vidéo',
  'ALTERNATIVEUPDATE_VIDEO_POSITION_LEFT' => 'Alignée à gauche',
  'ALTERNATIVEUPDATE_VIDEO_POSITION_RIGHT' => 'Alignée à droite',
  'ALTERNATIVEUPDATE_URL_DISPLAY_VIDEO' => 'Afficher le lecteur si le lien est une vidéo ?',

  // javascripts/fields/nbsubscription.js
  // Feature UUID : auj9-subscribe-to-entry
  'AUJ9_NB_SUBSCRIPTION' => 'Nombre d\'inscriptions',
  // javascripts/fields/subscribe.js
  // Feature UUID : auj9-subscribe-to-entry
  'AUJ9_FORM_EDIT_LIST_CAN_BE_EDITED_BY' => 'Liste pouvant être modifiée par',
  'AUJ9_SUBSCRIBE' => 'Inscriptions',
  'AUJ9_SUBSCRIBE_ENTRY_CREATION_PAGE_LABEL' => 'Page pour la création de fiche liée',
  'AUJ9_SUBSCRIBE_SHOWLIST' => 'Afficher la liste',
  'AUJ9_SUBSCRIBE_TYPE' => 'Type',
  'AUJ9_SUBSCRIBE_TYPE_ENTRY' => 'Fiche',
  'AUJ9_SUBSCRIBE_TYPE_USER' => 'Utilisateur',

  // javascripts/components/BazarSendMail.js
  // Feature UUID : auj9-bazar-list-send-mail-dynamic
  'AUJ9_SEND_MAIL_TEMPLATE_SENT' => 'Emails(s) envoyés (pour {details})',
  'AUJ9_SEND_MAIL_TEMPLATE_NOT_SENT' => 'Emails(s) non envoyés {errorMsg}',

  // javascripts/modified-bazar.js
  // Feature UUID : auj9-can-force-entry-save-for-specific-group
  'AUJ9_BAZAR_ERROR_FOR_ADMINS' => "Une erreur est survenue\n{msg}\nVoulez-vous forcer l'enregistrement malgré cette erreur ?",
];
