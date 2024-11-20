<?php

/**
 * @var aesis\user\models\User   $user
 * @var aesis\user\models\Token  $token
 */
?>
<?= Yii::t('user', 'Hello') ?>,

<?= Yii::t('user', 'We have received a request to delete the account associated with your email on {0}', Yii::$app->name) ?>.
<?= Yii::t('user', 'Please click the link below to confirm the deletion of your account') ?>.

<?= $token->url ?>

<?= Yii::t('user', 'If you cannot click the link, please try pasting the text into your browser') ?>.

<?= Yii::t('user', 'If you did not make this request, you can ignore this email, and your account will remain active') ?>.
