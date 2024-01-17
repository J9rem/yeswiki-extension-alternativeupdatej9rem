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
  'ALTERNATIVEUPDATE_ACTIVATE_EXT' => 'Activer',
  'ALTERNATIVEUPDATE_DEACTIVATE_EXT' => 'Désactiver',
  'ALTERNATIVEUPDATE_REINSTALL' => 'Réinstaller',
  'ALTERNATIVEUPDATE_YOUR_PASSWORD' => 'Votre mot de passe :',
  'ALTERNATIVEUPDATE_CONFIRM_PASSWORD' => 'Confirmez votre mot de passe',
  'ALTERNATIVEUPDATE_OTHER_UPDATE_METHOD' => 'Mettre à jour par l\'autre méthode',
  'ALTERNATIVEUPDATE_FINISH_UPDATE' => 'Terminer la mise à jour',
  // Feature UUID : auj9-choice-display-hidden-field
  'ALTERNATIVEUPDATEJ9REM_SHOW_FIELDS' => 'Afficher les champs masqués',
  'ALTERNATIVEUPDATEJ9REM_HIDE_FIELDS' => 'Masquer les champs masqués',

  // actions/AutoUpdateAreasAction.php
  // Feature UUID : auj9-autoupdateareas-action
  'AUJ9_AUTOUPDATE_AREAS_RESERVED_TO_ADMIN' => 'Lien de mise à jour uniquement accessible aux administrateurs connectés',
  'AUJ9_AUTOUPDATE_AREAS_TEXT' => 'Créer automatiquement la liste %{listName}',
  'AUJ9_AUTOUPDATE_AREAS_OF_DEPARTEMENTS' => 'des départements français',
  'AUJ9_AUTOUPDATE_AREAS_OF_AREAS' => 'des régions françaises',
  'AUJ9_AUTOUPDATE_AREAS_FORM' => ', le formulaire et les fiches associant régions et départements français',

  // actions/ClearLocalCacheAction.php
  // Feature UUID : auj9-local-cache
  'AUJ9_CLEAR_LOCAL_CACHE_RESERVED_TO_ADMIN' => 'Lien de suppression du cache local uniquement accessible aux administrateurs connectés',
  'AUJ9_CLEAR_LOCAL_CACHE_TEXT' => 'Supprimer le cache local',
  'AUJ9_CLEARING_LOCAL_CACHE_TEXT' => 'Suppression du cache local',

  // actions/EditEntryPartialAction.php
  // Feature UUID : auj9-editentrypartial-action
  'AUJ9_EDIT_PARTIAL_ENTRY_ERROR_REGISTER' => 'Une erreur est survenue lors de l\'enregistrement de la fiche',
  'AUJ9_EDIT_ENTRY_PARTIAL_WRONG_PARAMS' => 'Les paramètres de l\'action {{editentrypartials}} semblent avoir été modifiés par un compte non administrateur ou sans passer par le bouton \'composants\'. L\'action est donc désactivée !',
  'AUJ9_ID_PARAM_NOT_EMPTY' => 'le paramètre \'id\' ne doit pas être vide',
  'AUJ9_ID_PARAM_SHOULD_BE_A_FORM' => 'le paramètre \'id\' devrait correspondre à un formulaire existant',
  'AUJ9_ID_PARAM_SHOULD_BE_NUMBER' => 'le paramètre \'id\' devrait être un nombre positif',
  'AUJ9_FIELDS_PARAM_NOT_EMPTY' => 'le paramètre \'fields\' ne doit pas être vide',

  // controllers/ApiContoller.php
  'AUJ9_SEND_MAIL_TEMPLATE_CONTACTEMAIL' => 'Destinataire(s)',
  'AUJ9_SEND_MAIL_TEMPLATE_ONEEMAIL' => 'en un seul e-mail groupé',
  'AUJ9_SEND_MAIL_TEMPLATE_ONEBYONE' => 'un envoi d\'email par destinataire',
  'AUJ9_SEND_MAIL_TEMPLATE_REPLYTO' => 'Répondre à',
  'AUJ9_SEND_MAIL_TEMPLATE_HIDDENCOPY' => 'Copie cachée à',

  // fields/SubscribeField.php
  // Feature UUID : auj9-subscribe-to-entry
  'AUJ9_SUBSCRIBE_BAD_CONFIG_ENTRY_CREATION_PAGE' => 'Le champ "subscribe" est mal configuré car la page de création de fiche liée est mal définie.',
  'AUJ9_SUBSCRIBE_BAD_CONFIG_FORM' => 'Le champ "subscribe" est mal configuré car le formulaire associé n\'a pas activé l\'option "restreindre à une seule fiche par utilisateur"',
  'AUJ9_SUBSCRIBE_HIDE_LIST' => 'Cacher la liste',
  'AUJ9_SUBSCRIBE_HINT_FOR_MAX' => '-1 = pas de limite',
  'AUJ9_SUBSCRIBE_LABEL_FOR_MAX' => 'Nombre maximum d\'inscriptions',
  'AUJ9_SUBSCRIBE_SEE_LIST' => 'Voir la liste',

  // fields/video.twig
  // Feature UUID : auj9-video-field
  'VIDEO_LINK_FIELD' => 'Lien vers la vidéo : %{link}',

  // handlers/DuplicationHandler.php
  // Feature UUID : auj9-duplicate
  'AUJ9_DUPLICATE' => 'Dupliquer',
  'AUJ9_DUPLICATION_IN_COURSE' => 'Vous être en train de dupliquer la page \'%{originTag}\' vers \'%{destinationTag}\' !',
  'AUJ9_DUPLICATION_NOT_POSSIBLE_IF_EXISTING' => 'Copie de la page impossible car le nouveau nom choisi est déjà utilisé !',
  'AUJ9_DUPLICATION_NOT_POSSIBLE_IF_NO_NAME' => 'Copie de la page impossible car le nouveau nom choisi est vide !',
  'AUJ9_DUPLICATION_TROUBLE' => 'La duplication de la présente fiche a bien eu lieu mais il n\'a pas été possible de retrouver le lien vers la nouvelle fiche.',
  'AUJ9_OTHER_ENTRIES_CREATED' => 'D\'autres fiches ont été créées : %{links}',
  'AUJ9_PAGE_NEW_NAME' => 'Nom de la nouvelle page à créer',
  'AUJ9_SAVE' => 'Sauver',
  
  // services/SubscriptionManager.php
  // Feature UUID : auj9-subscribe-to-entry
  'AUJ9_SUBSCRIBE_EMPTY' => '0 inscription',
  'AUJ9_SUBSCRIBE_ONE_SUBSCRIPTION' => '1 inscription',
  'AUJ9_SUBSCRIBE_MANY_SUBSCRIPTIONS' => '%{X} inscriptions',

  // services/ActionsBuilderService.php
  // Feature UUID : auj9-editentrypartial-action
  'AUJ9_EDIT_ENTRY_PARTIAL_ACTION_FIELDS_LABEL' => 'Champs à modifier',
  'AUJ9_EDIT_ENTRY_PARTIAL_ACTION_LABEL' => 'Modifier partiellement une fiche',
  // Feature UUID : auj9-bazar-list-video-dynamic
  'AUJ9_BAZARVIDEO_ACTION_LABEL' => 'Affichage des vidéos par bloc',
  'AUJ9_BAZARVIDEO_ACTION_VIDEO_LINK_LABEL' => 'Champ lien',
  // Feature UUID : auj9-breadcrumbs-action
  'AUJ9_BREADCRUMBS_DISPLAY_DROPDOWN_LABEL' => 'Afficher les menus déroulants',
  'AUJ9_BREADCRUMBS_DISPLAY_DROPDOWN_ONLY_FOR_LAST_LABEL' => 'Afficher les menus déroulants uniquement pour le dernier niveau',
  'AUJ9_BREADCRUMBS_LABEL' => 'Menu de type fil d\'Ariane',
  'AUJ9_BREADCRUMBS_SEPARATOR_LABEL' => 'Séparateur',
  'AUJ9_BREADCRUMBS_SEPARATOR_HINT' => 'Syntaxe particulière : soit une chaîne de caractères, soit la syntaxe "span.nom-classe:contenu:span" (fonctionne aussi pour les icones "i.icon-class::i" ou "b:texte en gras:b")',
  'AUJ9_BREADCRUMBS_PAGE_LABEL' => 'Page contenant le menu',

  // templates/bazar/fields/date.twig
  'EVENT_IS_RECURRENT' => 'Cet évènement est récurrent : %{repetition}, %{nb} fois maximum',
  'EVENT_LIMIT_DATE' => 'jusqu\'au %{date}',
  'EVENT_REPETITION_FOR_DAYS' => 'tous les %{x} jours',
  'EVENT_REPETITION_FOR_MONTHS' => 'tous les %{x} mois, %{monthRepetition}',
  'EVENT_REPETITION_FOR_WEEKS' => 'toutes les %{x} semaines, %{days}',
  'EVENT_REPETITION_FOR_YEAR' => 'tous les ans, %{monthRepetition}',
  'EVENT_REPETITION_FOR_YEARS' => 'toutes les %{x} années, %{monthRepetition}',
  'EVENT_REPETITION_NTH_OF_MONTH' => 'le %{nth} %{month}',
  'EVENT_REPETITION_NTH_OF_MONTH_ALONE' => 'le %{nth}',
  'EVENT_REPETITION_IN_MONTH' => 'en %{month}, ',
  'EVENT_IS_LINKED_TO_RECURRENT' => 'Cet évènement est un évènement récurrent lié à la fiche %{link}',

  // templates/bazar/inputs/date.twig
  'EVENT_EVERY_DAYS' => 'Tous les jours',
  'EVENT_EVERY_X_DAYS' => 'Tous les X jours',
  'EVENT_EVERY_WEEKS' => 'Toutes les semaines',
  'EVENT_EVERY_X_WEEKS' => 'Toutes les X semaines',
  'EVENT_EVERY_MONTHS' => 'Tous les mois',
  'EVENT_EVERY_X_MONTHS' => 'Tous les X mois',
  'EVENT_EVERY_X_MONTHS' => 'Tous les X mois',
  'EVENT_EVERY_YEARS' => 'Tous les ans',
  'EVENT_EVERY_X_YEARS' => 'Toutes les X années',
  'EVENT_EXCEPT_LABEL' => 'Sauf',
  'EVENT_FIRST_Y_OF_MONTH' => 'Le premier Y du mois',
  'EVENT_FORTH_Y_OF_MONTH' => 'Le quatrième Y du mois',
  'EVENT_IS_LINKED_TO_RECURRENT_EDIT' => 'Cette évènement est un évènement récurrent lié à la fiche %{link}.<br/>Toute modification de cette fiche cassera le lien avec l\'évènement de base !',
  'EVENT_LAST_Y_OF_MONTH' => 'Le dernier Y du mois',
  'EVENT_NB_MAX_REPETITIONS' => '%{X} fois maximum',
  'EVENT_NO_REPETITION' => 'Pas de répétition',
  'EVENT_NTH_OF_MONTH' => 'Chaque Y du mois',
  'EVENT_ON_MONTH' => 'En :',
  'EVENTS_REPETITIONS' => 'Répétitions',
  'EVENT_SECOND_Y_OF_MONTH' => 'Le second Y du mois',
  'EVENT_THIRD_Y_OF_MONTH' => 'Le troisième Y du mois',
  'EVENT_UP_TO_DATE' => 'Jusqu\'au :',
  'EVENTS_WHEN_IN_MONTH' => 'A quel moment du mois ?',

  // templates/edit-entry-partial-action.twig
  // Feature UUID : auj9-editentrypartial-action
  'AUJ9_EDIT_PARTIAL_NO_ENTRY' => 'Aucune fiche du formulaire <code>%{form}</code> n\'est modifiable pour votre compte utilisateur',
  'AUJ9_EDIT_PARTIAL_SELECT_ENTRY' => 'Fiche sélectionnée',

  // EditConfig
  // Feature UUID : auj9-local-cache
  'EDIT_CONFIG_HINT_LOCALCACHE[LIMITEDGROUPS]' => 'Liste des groupes autorisés au cache séparées par des virgules (vide = tout le monde, "!+" = non connecté)',
  // Feature UUID : auj9-fix-edit-metadata
  'EDIT_CONFIG_HINT_CLEANUNUSEDMETADATA' => 'Nettoyer les metadonnées non utilisées (true/false)',
  // Feature UUID : auj9-bazar-list-send-mail-dynamic
  'EDIT_CONFIG_HINT_DEFAULT-SENDER-EMAIL' => 'E-mail par défaut pour le template "send-mail.twig"',
  // Feature UUID : auj9-feat-user-controller-delete-own-pages 
  'EDIT_CONFIG_HINT_DELETEPAGESANDENTRIESWITHUSER' => 'En supprimant un utilisateur, supprimer aussi les pages et fiches liées ? (true/false)',
  // Feature UUID : auj9-bazarlist-filter-order 
  'EDIT_CONFIG_HINT_SORTLISTASDEFINEDINFILTERS' => 'Garder l\'ordre des listes dans les filtres bazar',

  // docs/actions/bazarliste.yaml via templates/aceditor/actions-builder.tpl.html
  // Feature UUID : auj9-bazar-list-send-mail-dynamic
  'AUJ9_SEND_MAIL_TEMPLATE_DEFAULTCONTENT' => 'Bonjour,<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----Complétez votre message ici----<br/>',
  'AUJ9_SEND_MAIL_TEMPLATE_DEFAULTCONTENT_LABEL' => 'Contenu par défaut',
  'AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_SENDERNAME_LABEL' => 'Nom d\'expéditeur par défaut',
  'AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_SUBJECT_LABEL' => 'Sujet par défaut',
  'AUJ9_SEND_MAIL_TEMPLATE_DESCRIPTION' => 'Permet d\'envoyer des e-mails à un groupe de personnes',
  'AUJ9_SEND_MAIL_TEMPLATE_EMAILFIELDNAME_LABEL' => 'Champ pour l\'email',
  'AUJ9_SEND_MAIL_TEMPLATE_GROUP_IN_HIDDIN_COPY_LABEL' => 'Par défaut, envoyer en copie caché si envoi non groupé',
  'AUJ9_SEND_MAIL_TEMPLATE_LABEL' => 'Envoyer des e-mails',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDTOGROUPDEFAULT_LABEL' => 'Par défaut, envoyer à tous',
  'AUJ9_SEND_MAIL_TEMPLATE_TITLE_EMPTY_LABEL' => 'Vide = \'%{emptyVal}\'',
  'AUJ9_SEND_MAIL_TEMPLATE_TITLE_LABEL' => 'Titre',
  
  // templates/bazar/send-mail.twig
  // Feature UUID : auj9-bazar-list-send-mail-dynamic
  'AUJ9_SEND_MAIL_TEMPLATE_ADDCONTACTSTOREPLYTO' => 'Forcer "Répondre à tous" (uniquement pour les envois groupés)',
  'AUJ9_SEND_MAIL_TEMPLATE_ADDSENDERTOCONTACT' => 'Ajouter l\'expéditeur dans les destinataires',
  'AUJ9_SEND_MAIL_TEMPLATE_ADDSENDERTOREPLYTO' => 'Ajouter l\'expéditeur dans "Répondre à"',
  'AUJ9_SEND_MAIL_TEMPLATE_ADMINPART' => 'Visible uniquement par les administrateurices du site',
  'AUJ9_SEND_MAIL_TEMPLATE_CHECKALL' => 'Cocher tout ce qui est visible',
  'AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_TITLE' => 'Envoyer un e-mail à :',
  'AUJ9_SEND_MAIL_TEMPLATE_DONE_FOR' => 'Envoyé pour',
  'AUJ9_SEND_MAIL_TEMPLATE_GROUP_IN_HIDDEN_COPY' => 'Envoyer en copie cachée',
  'AUJ9_SEND_MAIL_TEMPLATE_GROUP_IN_HIDDEN_COPY_HELP' => 'Option uniquement disponible si moins de {nb} fiches sélectionnées',
  'AUJ9_SEND_MAIL_TEMPLATE_HASCONTACTFROM' => "Attention, ce wiki force l'expéditeur des e-mails à %{forcedFrom}\n".
      "(l'e-mail de l'expéditeur est déplacé dans \"Répondre à\")",
  'AUJ9_SEND_MAIL_TEMPLATE_HELP' => "Pour les envois non groupés :\n".
      "[text](lien) => lien href\n".
      "{baseUrl} => lien de base du wiki\n".
      "{entryId} => entryId\n".
      "{entryLink} => lien vers la fiche brut\n".
      "{entryLinkWithTitle} => lien vers la fiche avec son titre\n".
      "{entryLinkWithText} => lien vers la fiche avec le texte \"Voir la fiche xxx\"\n".
      "{entryEditLink} => lien vers la modification de la fiche (brut)\n".
      "{entryEditLinkWithText} => lien vers la modification de la fiche (avec le titre \"Modifier la fiche\")\n".
      "{entry[fieldName]} => valeur du champ fieldName pour cette fiche\n",
  'AUJ9_SEND_MAIL_TEMPLATE_HIDE' => 'Masquer les paramètres avancés',
  'AUJ9_SEND_MAIL_TEMPLATE_HIDE_DONE_FOR_ALL' => 'Réduire la liste',
  'AUJ9_SEND_MAIL_TEMPLATE_LAST_UPDATE' => 'Dernière maj : %{date}',
  'AUJ9_SEND_MAIL_TEMPLATE_MESSAGE' => 'Message',
  'AUJ9_SEND_MAIL_TEMPLATE_MESSAGE_SUBJECT' => 'Sujet du message',
  'AUJ9_SEND_MAIL_TEMPLATE_MESSAGE_SUBJECT_PLACEHOLDER' => 'Indiquez l\'objet de l\'e-mail',
  'AUJ9_SEND_MAIL_TEMPLATE_NOCONTACTS' => 'Pas d\'e-mail car pas de destinataires !',
  'AUJ9_SEND_MAIL_TEMPLATE_PLURAL_NB_DEST_TEXT' => 'Actuellement {nb} destinataires',
  'AUJ9_SEND_MAIL_TEMPLATE_PREVIEW' => 'Aperçu',
  'AUJ9_SEND_MAIL_TEMPLATE_RECEIVEHIDDENCOPY' => 'Recevoir une copie cachée',
  'AUJ9_SEND_MAIL_TEMPLATE_RETURN_PARAM' => 'Retourner aux paramètres',
  'AUJ9_SEND_MAIL_TEMPLATE_SECURITY_HIDDEN' => 'masqué par sécurité',
  'AUJ9_SEND_MAIL_TEMPLATE_SEE' => 'Voir les paramètres avancés',
  'AUJ9_SEND_MAIL_TEMPLATE_SEE_DRAFT' => 'Voir le brouillon',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDEREMAIL' => 'E-mail de l\'expéditeur',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDERNAME' => 'Nom de l\'expéditeur',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDMAIL' => 'Envoyer le(s) mail(s)',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDMAIL_CANCEL' => 'Annuler l\'envoi',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDMAIL_CONFIRMATION' => 'Cliquer pour confirmer l\'envoi',
  'AUJ9_SEND_MAIL_TEMPLATE_SENDTOGROUP' => 'Faire un envoi groupé (tout le monde voit la liste de destinataires)',
  'AUJ9_SEND_MAIL_TEMPLATE_SHOW_DONE_FOR_ALL' => 'Montrer toute la liste',
  'AUJ9_SEND_MAIL_TEMPLATE_SINGULAR_NB_DEST_TEXT' => 'Actuellement {nb} destinataire',
  'AUJ9_SEND_MAIL_TEMPLATE_SIZE' => 'Taille :',
  'AUJ9_SEND_MAIL_TEMPLATE_UNCHECKALL' => 'Décocher tout ce qui est visible',

  // templates/open-agenda-config.twig
  // Feature UUID : auj9-open-agenda-connect
  'AUJ9_OPEN_AGENDA_ACTIVATED' => 'Fonctionnalité activée ?',
  'AUJ9_OPEN_AGENDA_CONFIG_ASSOCIATIONS' => 'Associations',
  'AUJ9_OPEN_AGENDA_CONFIG_TITLE' => 'Configuration de la connexion à Open Agenda',
  'AUJ9_OPEN_AGENDA_CONFIG_KEYS' => 'Clés privées',
  'AUJ9_OPEN_AGENDA_CONFIG_TESTKEY' => 'Tester la clé',
  'AUJ9_OPEN_AGENDA_ERROR' => 'Une erreur est survenue',
  'AUJ9_OPEN_AGENDA_GET_FORM' => 'Recherche des formulaires',
  'AUJ9_OPEN_AGENDA_NEW_FORM' => 'Formulaire',
  'AUJ9_OPEN_AGENDA_NEW_FORM_ID' => 'Identifiant Open Agenda',
  'AUJ9_OPEN_AGENDA_NEW_FORM_KEY' => 'Clé privée associée',
  'AUJ9_OPEN_AGENDA_NEW_FORM_PUBLIC' => 'Clé publique',
  'AUJ9_OPEN_AGENDA_NEW_KEY_NAME' => 'Nom de la nouvelle clé',
  'AUJ9_OPEN_AGENDA_NEW_KEY_VALUE' => 'Valeur de la nouvelle clé',
  'AUJ9_OPEN_AGENDA_REGISTERING_ASSOCIATION' => 'Enregistrement de l\'association',
  'AUJ9_OPEN_AGENDA_REGISTERING_KEY' => 'Enregistrement de la clé',
  'AUJ9_OPEN_AGENDA_REMOVING_KEY' => 'Retrait de la clé {name}',
  'AUJ9_OPEN_AGENDA_YOU_TURN' => 'A votre tour !',

  // templates/button-for-subscription.twig
  // Feature UUID : auj9-subscribe-to-entry
  'AUJ9_CONNECT_TO_REGISTER' => 'Se connecter pour s\'inscrire',
  'AUJ9_CREATE_ENTRY_TO_REGISTER' => 'Créer une fiche pour s\'inscrire',
  'AUJ9_NO_WRITE_TO_REGISTER' => 'Vous n\'avez pas les droits suffisants pour vous modifier votre inscription !',
  'AUJ9_REGISTER' => 'S\'inscrire',
  'AUJ9_REGISTER_NO_PLACE' => 'Il n\'y a plus de place disponible',
  'AUJ9_UNREGISTER' => 'Se désinscrire',
];
