<?php

namespace Crm\PrintModule\Components;

use Crm\ApplicationModule\Widget\BaseWidget;
use Crm\ApplicationModule\Widget\WidgetManager;
use Crm\PaymentsModule\Repository\PaymentLogsRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\PrintModule\Forms\UserPrintAddressFormFactory;
use Crm\SalesFunnelModule\Presenters\SalesFunnelPresenter;
use Crm\SubscriptionsModule\PaymentItem\SubscriptionTypePaymentItem;

/**
 * PaymentSuccessPrintWidget is directly targeted to be used in \Crm\SalesFunnelModule\Presenters\SalesFunnelPresenter
 * and extends the success page with invoice form.
 * Any other usage ends up with Exception.
 */
class PaymentSuccessPrintWidget extends BaseWidget
{
    protected $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'payment_success_print_widget.latte';

    private $paymentLogsRepository;

    private $paymentsRepository;

    private $payment;

    public function __construct(
        WidgetManager $widgetManager,
        PaymentLogsRepository $paymentLogsRepository,
        PaymentsRepository $paymentsRepository
    ) {
        parent::__construct($widgetManager);
        $this->paymentLogsRepository = $paymentLogsRepository;
        $this->paymentsRepository = $paymentsRepository;
    }

    public function identifier()
    {
        return 'paymentsuccessprintwidget';
    }

    public function render()
    {
        $payment = $this->presenter()->getPayment();
        if ($payment->status !== PaymentsRepository::STATUS_PAID) {
            return;
        }
        if (!$this->isPrintAddressRequired($payment)) {
            return;
        }


        $this->template->payment = $payment;
        $this->template->setFile($this->templatePath);
        $this->template->render();
    }

    public function createComponentUserPrintAddressForm(UserPrintAddressFormFactory $factory)
    {
        $payment = $this->presenter()->getPayment();

        $form = $factory->create($payment);
        $factory->onSave = function ($form, $user) {
            $form['done']->setValue(1);
            $this->redrawControl('printAddressFormSnippet');
        };

        return $form;
    }

    public function presenter(): SalesFunnelPresenter
    {
        $presenter = $this->getPresenter();
        if (!$presenter instanceof SalesFunnelPresenter) {
            throw new \Exception('PaymentSuccessPrintWidget used within not allowed presenter: ' . get_class($presenter));
        }
        return $presenter;
    }

    private function isPrintAddressRequired($payment)
    {
        foreach ($payment->related('payment_items') as $paymentItem) {
            if ($paymentItem->type !== SubscriptionTypePaymentItem::TYPE) {
                return false;
            }
            $subscriptionType = $paymentItem->subscription_type;
            if (!$subscriptionType) {
                continue;
            }
            if ($subscriptionType->ask_address) {
                return true;
            }

            // TODO: this might be redundant due to ask_address
            foreach ($subscriptionType->related('subscription_type_content_access') as $stca) {
                if (in_array($stca->content_access->name, ['print', 'print_friday'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
