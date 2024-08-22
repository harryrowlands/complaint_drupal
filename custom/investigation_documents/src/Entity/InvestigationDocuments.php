<?php

declare(strict_types=1);

namespace Drupal\investigation_documents\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\investigation_documents\InvestigationDocumentsInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the investigationdocuments entity class.
 *
 * @ContentEntityType(
 *   id = "investigation_documents_investigationdocuments",
 *   label = @Translation("InvestigationDocuments"),
 *   label_collection = @Translation("InvestigationDocumentss"),
 *   label_singular = @Translation("investigationdocuments"),
 *   label_plural = @Translation("investigationdocumentss"),
 *   label_count = @PluralTranslation(
 *     singular = "@count investigationdocumentss",
 *     plural = "@count investigationdocumentss",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\investigation_documents\InvestigationDocumentsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\investigation_documents\InvestigationDocumentsAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\investigation_documents\Form\InvestigationDocumentsForm",
 *       "edit" = "Drupal\investigation_documents\Form\InvestigationDocumentsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "investigation_documents_investigationdocuments",
 *   data_table = "investigation_documents_investigationdocuments_field_data",
 *   revision_table = "investigation_documents_investigationdocuments_revision",
 *   revision_data_table = "investigation_documents_investigationdocuments_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer investigation_documents_investigationdocuments",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/investigationdocuments",
 *     "add-form" = "/investigationdocuments/add",
 *     "canonical" = "/investigationdocuments/{investigation_documents_investigationdocuments}",
 *     "edit-form" = "/investigationdocuments/{investigation_documents_investigationdocuments}/edit",
 *     "delete-form" = "/investigationdocuments/{investigation_documents_investigationdocuments}/delete",
 *     "delete-multiple-form" = "/admin/content/investigationdocuments/delete-multiple",
 *     "revision" = "/investigationdocuments/{investigation_documents_investigationdocuments}/revision/{investigation_documents_investigationdocuments_revision}/view",
 *     "revision-delete-form" = "/investigationdocuments/{investigation_documents_investigationdocuments}/revision/{investigation_documents_investigationdocuments_revision}/delete",
 *     "revision-revert-form" = "/investigationdocuments/{investigation_documents_investigationdocuments}/revision/{investigation_documents_investigationdocuments_revision}/revert",
 *     "version-history" = "/investigationdocuments/{investigation_documents_investigationdocuments}/revisions",
 *   },
 *   field_ui_base_route = "entity.investigation_documents_investigationdocuments.settings",
 * )
 */
final class InvestigationDocuments extends RevisionableContentEntityBase implements InvestigationDocumentsInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the investigationdocuments was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the investigationdocuments was last edited.'));

    return $fields;
  }

}
