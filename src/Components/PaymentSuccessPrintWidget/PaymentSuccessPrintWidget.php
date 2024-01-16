<?php

namespace Crm\PrintModule\Components\PaymentSuccessPrintWidget;

use Crm\ApplicationModule\Widget\BaseLazyWidget;
use Crm\PaymentsModule\Gateways\BankTransfer;
use Crm\PaymentsModule\PaymentAwareInterface;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\PrintModule\Forms\UserPrintAddressFormFactory;
use Crm\SubscriptionsModule\PaymentItem\SubscriptionTypePaymentItem;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;

/**
 * PaymentSuccessPrintWidget is directly targeted to be used in \Crm\SalesFunnelModule\Presenters\SalesFunnelPresenter
 * and extends the success page with invoice form.
 * Any other usage ends up with Exception.
 */
class PaymentSuccessPrintWidget extends BaseLazyWidget
{
    protected $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'payment_success_print_widget.latte';

    public function identifier()
    {
        return 'paymentsuccessprintwidget';
    }

    public function render()
    {
        $payment = $this->getPayment();
        if ($payment->status !== PaymentsRepository::STATUS_PAID && $payment->payment_gateway->code !== BankTransfer::GATEWAY_CODE) {
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
        $payment = $this->getPayment();

        $form = $factory->create($payment);
        $factory->onSave = function ($form, $user) {
            $form['done']->setValue(1);
            $this->redrawControl('printAddressFormSnippet');
        };
        $form->onError[] = function (Form $form) {
            $this->redrawControl('printAddressFormSnippet');
        };

        return $form;
    }

    public function getPayment(): ActiveRow
    {
        $presenter = $this->getPresenter();
        if ($presenter instanceof PaymentAwareInterface) {
            return $presenter->getPayment();
        }

        throw new \Exception('PaymentSuccessPrintWidget used within not allowed presenter: ' . get_class($presenter));
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
                if (in_array($stca->content_access->name, ['print', 'print_friday'], true)) {
                    return true;
                }
            }
        }
        return false;
    }
}
