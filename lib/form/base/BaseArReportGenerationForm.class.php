<?php

/**
 * ArReportGeneration form base class.
 *
 * @method ArReportGeneration getObject() Returns the current form's model object
 *
 * @package    asterisell
 * @subpackage form
 * @author     Your name here
 */
abstract class BaseArReportGenerationForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'          => new sfWidgetFormInputHidden(),
      'name'        => new sfWidgetFormInputText(),
      'description' => new sfWidgetFormInputText(),
    ));

    $this->setValidators(array(
      'id'          => new sfValidatorChoice(array('choices' => array($this->getObject()->getId()), 'empty_value' => $this->getObject()->getId(), 'required' => false)),
      'name'        => new sfValidatorString(array('max_length' => 256, 'required' => false)),
      'description' => new sfValidatorString(array('max_length' => 1024, 'required' => false)),
    ));

    $this->widgetSchema->setNameFormat('ar_report_generation[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'ArReportGeneration';
  }


}
