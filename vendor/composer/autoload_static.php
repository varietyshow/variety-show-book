<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit33cad13092641f81b77ea1ff6e1ca5db
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'OmiseAccount' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseAccount.php',
        'OmiseApiResource' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/res/OmiseApiResource.php',
        'OmiseAuthenticationFailureException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseBadRequestException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseBalance' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseBalance.php',
        'OmiseCapabilities' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseCapabilities.php',
        'OmiseCard' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseCard.php',
        'OmiseCardList' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseCardList.php',
        'OmiseChain' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseChain.php',
        'OmiseCharge' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseCharge.php',
        'OmiseCustomer' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseCustomer.php',
        'OmiseDispute' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseDispute.php',
        'OmiseEvent' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseEvent.php',
        'OmiseException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseFailedCaptureException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseFailedFraudCheckException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseFailedRefundException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseForex' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseForex.php',
        'OmiseInvalidBankAccountException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseInvalidCardException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseInvalidCardTokenException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseInvalidChargeException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseInvalidLinkException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseInvalidRecipientException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseLink' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseLink.php',
        'OmiseMissingCardException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseNotFoundException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseObject' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/res/obj/OmiseObject.php',
        'OmiseOccurrence' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseOccurrence.php',
        'OmiseOccurrenceList' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseOccurrenceList.php',
        'OmiseReceipt' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseReceipt.php',
        'OmiseRecipient' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseRecipient.php',
        'OmiseRefund' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseRefund.php',
        'OmiseRefundList' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseRefundList.php',
        'OmiseSchedule' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseSchedule.php',
        'OmiseScheduleList' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseScheduleList.php',
        'OmiseScheduler' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseScheduler.php',
        'OmiseSearch' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseSearch.php',
        'OmiseSource' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseSource.php',
        'OmiseToken' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseToken.php',
        'OmiseTransaction' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseTransaction.php',
        'OmiseTransfer' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/OmiseTransfer.php',
        'OmiseUndefinedException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseUsedTokenException' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/exception/OmiseExceptions.php',
        'OmiseVaultResource' => __DIR__ . '/..' . '/omise/omise-php/lib/omise/res/OmiseVaultResource.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit33cad13092641f81b77ea1ff6e1ca5db::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit33cad13092641f81b77ea1ff6e1ca5db::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit33cad13092641f81b77ea1ff6e1ca5db::$classMap;

        }, null, ClassLoader::class);
    }
}
