<?php
/* @var $this TrialController */
/* @var $model Trial
 * @var $dataProviders CActiveDataProvider[]
 */

$hasEditPermissions = Trial::checkTrialAccess(Yii::app()->user, $model->id, UserTrialPermission::PERMISSION_EDIT);
$hasManagePermissions = Trial::checkTrialAccess(Yii::app()->user, $model->id, UserTrialPermission::PERMISSION_MANAGE);
?>

<h1 class="badge">Trial</h1>
<div class="row">
  <div class="large-9 column">
    <div class="box admin">

        <?php if ($model->trial_type == Trial::TRIAL_TYPE_INTERVENTION): ?>
          <div class="alert-box alert with-icon">
            This is an Intervention Trial. Patients accepted into this Trial cannot be accepted into other Intervention
            Trials
          </div>
        <?php endif; ?>

        <?php if ($model->status == Trial::STATUS_CANCELLED): ?>
          <div class="alert-box alert with-icon">This Trial has been cancelled. You will need to reopen it before you
            can make any changes.
          </div>
        <?php elseif ($model->status == Trial::STATUS_CLOSED): ?>
          <div class="alert-box alert with-icon">This Trial has been closed. You will need to reopen it before you
            can make any changes.
          </div>
        <?php endif; ?>
      <div class="row">
        <div class="large-9 column">
          <h1 style="display: inline"><?php echo $model->name; ?>

          </h1>
            <h3 style="display: inline">
            <?php if ($model->status != Trial::STATUS_CANCELLED && $hasEditPermissions): ?>
                  <?php echo CHtml::link('<u>edit</u>', array(
                          '/OETrial/trial/update',
                          'id' => $model->id,
                      )); ?>
            <?php endif; ?>
            <?php echo Chtml::encode('owned by ' . $model->ownerUser->first_name . ' ' . $model->ownerUser->last_name); ?>
          </h3>
        </div>
        <div class="large-3 column">
            <?php echo $model->getCreatedDateForDisplay(); ?> &mdash; <?php echo $model->getClosedDateForDisplay() ?>
        </div>
      </div>

        <?php if ($model->description !== ''): ?>
          <div class="row">
            <div class="large-12 column">
              <p><?php echo CHtml::encode($model->description); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($model->external_reference !== ''): ?>
          <div class="row">
            <div class="large-12 column">
              <p><?php echo CHtml::encode($model->external_reference); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($hasManagePermissions): ?>
          <br/>
            <?php if (in_array($model->status,
                array(Trial::STATUS_OPEN, Trial::STATUS_CANCELLED, Trial::STATUS_CLOSED))): ?>
                <?php echo CHtml::button($model->status == Trial::STATUS_OPEN ? 'Start Trial' : 'Re-open Trial',
                    array(
                        'id' => 'start-trial-button',
                        'class' => 'small button primary event-action',
                        'onclick' => "changeTrialState($model->id, " . Trial::STATUS_IN_PROGRESS . ')',
                    )); ?>
            <?php endif; ?>

            <?php if ($model->status == Trial::STATUS_IN_PROGRESS): ?>
                <?php echo CHtml::button('Close Trial', array(
                    'id' => 'close-trial-button',
                    'class' => 'small button primary event-action',
                    'onclick' => "changeTrialState($model->id, " . Trial::STATUS_CLOSED . ')',
                )); ?>

            <?php endif; ?>

            <?php if (in_array($model->status, array(Trial::STATUS_OPEN, Trial::STATUS_IN_PROGRESS))): ?>
                <?php echo CHtml::button('Cancel Trial', array(
                    'id' => 'cancel-trial-button',
                    'class' => 'small button primary event-action',
                    'onclick' => "changeTrialState($model->id, " . Trial::STATUS_CANCELLED . ')',
                )); ?>
            <?php endif; ?>
        <?php endif; ?>
      <hr/>
      <h2>Shortlisted Patients</h2>
        <?php $this->widget('zii.widgets.CListView', array(
            'id' => 'shortlistedPatientList',
            'dataProvider' => $dataProviders[TrialPatient::STATUS_SHORTLISTED],
            'itemView' => '/trialPatient/_view',
        )); ?>
      <hr/>
      <h2>Accepted Patients</h2>
        <?php $this->widget('zii.widgets.CListView', array(
            'id' => 'acceptedPatientList',
            'dataProvider' => $dataProviders[TrialPatient::STATUS_ACCEPTED],
            'itemView' => '/trialPatient/_view',
        )); ?>
      <hr/>
      <h2>Rejected Patients</h2>
        <?php $this->widget('zii.widgets.CListView', array(
            'id' => 'rejectedPatientList',
            'dataProvider' => $dataProviders[TrialPatient::STATUS_REJECTED],
            'itemView' => '/trialPatient/_view',
        )); ?>

    </div>

  </div><!-- /.large-9.column -->

  <div class="large-3 column">
    <div class="box generic">
        <?php if ($model->status != Trial::STATUS_CANCELLED && $hasEditPermissions): ?>
          <p>
                <span class="highlight">
                    <?php echo CHtml::link('Search for patients to add',
                        Yii::app()->createUrl('/OECaseSearch/caseSearch', array('trial_id' => $model->id))); ?>
                </span>
          </p>
        <?php endif; ?>

        <?php if ($hasManagePermissions): ?>
          <p>
                <span class="highlight">
                    <?php echo CHtml::link('Share this trial with other users',
                        Yii::app()->createUrl('/OETrial/trial/permissions', array('id' => $model->id))); ?>
                </span>
          </p>
        <?php endif; ?>

        <?php echo CHtml::beginForm($this->createUrl('report/downloadReport')); ?>
      <p>
          <span class="highlight">
              <?php echo CHtml::hiddenField('report-name', 'Cohort'); ?>
              <?php echo CHtml::hiddenField('trialID', $model->id); ?>
              <?php if (Yii::app()->user->checkAccess('OprnGenerateReport')): ?>
                  <?php echo CHtml::linkButton('Download Report'); ?>
              <?php endif; ?>
          </span>
      </p>
        <?php echo CHtml::endForm(); ?>

    </div>
  </div>
</div>

<?php
$assetPath = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.assets'), false, -1);
Yii::app()->getClientScript()->registerScriptFile($assetPath . '/js/toggle-section.js');
?>

<script type="application/javascript">

  function changePatientStatus(object, trial_patient_id, new_status) {
    $.ajax({
      url: '<?php echo Yii::app()->controller->createUrl('/OETrial/trialPatient/changeStatus'); ?>/' + trial_patient_id + '?new_status=' + new_status,
      type: 'GET',
      success: function (response) {
        if (response == '<?php echo TrialPatientController::STATUS_CHANGE_CODE_OK; ?>') {
          $.fn.yiiListView.update('shortlistedPatientList');
          $.fn.yiiListView.update('acceptedPatientList');
          $.fn.yiiListView.update('rejectedPatientList');
        } else if (response == '<?php echo TrialPatientController::STATUS_CHANGE_CODE_ALREADY_IN_INTERVENTION; ?>') {
          new OpenEyes.UI.Dialog.Alert({
            content: "You can't accept this patient into your Trial because the patient has already been accepted into another Intervention trial."
          }).open();
        } else {
          alert("Unknown response code: " + response_code);
        }
      },
      error: function (response) {
        new OpenEyes.UI.Dialog.Alert({
          content: "Sorry, an internal error occurred and we were unable to change the patient status.\n\nPlease contact support for assistance."
        }).open();
      }
    });
  }

  function editExternalTrialIdentifier(trial_patient_id) {
    $("#ext-trial-id-form-" + trial_patient_id).show();
    $("#ext-trial-id-" + trial_patient_id).hide();
    $('#ext-trial-id-link-' + trial_patient_id).hide();
  }

  function saveExternalTrialIdentifier(trial_patient_id) {
    var external_id = $('#trial-patient-ext-id-' + trial_patient_id).val();
    $.ajax({
      url: '<?php echo Yii::app()->controller->createUrl('/OETrial/trialPatient/updateExternalId'); ?>',
      data: {id: trial_patient_id, new_external_id: external_id},
      type: 'GET',
      success: function (response) {
        $("#ext-trial-id-form-" + trial_patient_id).hide();

        var id_label = $("#ext-trial-id-" + trial_patient_id);
        id_label.text(external_id);
        id_label.show();

        $('#ext-trial-id-link-' + trial_patient_id).show();
      },
      error: function (response) {
        new OpenEyes.UI.Dialog.Alert({
          content: "Sorry, an internal error occurred and we were unable to change the external trial identifier.\n\nPlease contact support for assistance."
        }).open();
      }
    });
  }

  function updateTreatmentType(treatment_type_picker) {
    var trial_patient_id = $(treatment_type_picker).attr('data-trial-patient-id');
    var treatment_type = $(treatment_type_picker).val();

    $('#treatment-type-loader-' + trial_patient_id).show();
    $('#treatment-type-success-' + trial_patient_id).hide();

    $.ajax({
      url: '<?php echo Yii::app()->controller->createUrl('/OETrial/trialPatient/updateTreatmentType'); ?>',
      data: {id: trial_patient_id, treatment_type: treatment_type},
      type: 'GET',
      success: function (response) {
        $('#treatment-type-loader-' + trial_patient_id).hide();
        $('#treatment-type-success-' + trial_patient_id).show();
      },
      error: function (response) {
        new OpenEyes.UI.Dialog.Alert({
          content: "Sorry, an internal error occurred and we were unable to change the treatment type.\n\nPlease contact support for assistance."
        }).open();
      }
    });
  }


  function changeTrialState(trial_id, new_state) {

    $.ajax({
      url: '<?php echo $this->createUrl('transitionState'); ?>',
      data: {id: trial_id, new_state: new_state},
      type: 'GET',
      success: function (response) {
        if (response == '<?php echo TrialController::RETURN_CODE_OK; ?>') {
          location.reload();
        } else if (response == '<?php echo TrialController::RETURN_CODE_CANT_OPEN_SHORTLISTED_TRIAL; ?>') {
          new OpenEyes.UI.Dialog.Alert({
            content: "You can't start the trial while some patients are still shortlisted. Either accept or reject them into the Trial before continuing.."
          }).open();
        } else {
          alert("Unknown response code: " + response);
        }
      },
      error: function (response) {
        new OpenEyes.UI.Dialog.Alert({
          content: "Sorry, an internal error occurred and we were unable to transition the trial..\n\nPlease contact support for assistance."
        }).open();
      }
    });
  }
</script>
