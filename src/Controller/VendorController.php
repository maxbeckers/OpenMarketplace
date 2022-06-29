<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMultiVendorMarketplacePlugin\Controller;

use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\Vendor;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\VendorInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\VendorProfileUpdate;
use BitBag\SyliusMultiVendorMarketplacePlugin\Exception\ShopUserNotFoundException;
use BitBag\SyliusMultiVendorMarketplacePlugin\Provider\VendorProviderInterface;
use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface;
use Sylius\Bundle\ResourceBundle\Controller\FlashHelperInterface;
use Sylius\Bundle\ResourceBundle\Controller\NewResourceFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\RedirectHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Controller\ResourceDeleteHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceFormFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesCollectionProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceUpdateHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\SingleResourceProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\StateMachineInterface;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

final class VendorController extends ResourceController
{
    private VendorProviderInterface $vendorProvider;

    public function __construct(
        MetadataInterface $metadata,
        RequestConfigurationFactoryInterface $requestConfigurationFactory,
        ?ViewHandlerInterface $viewHandler,
        RepositoryInterface $repository,
        FactoryInterface $factory,
        NewResourceFactoryInterface $newResourceFactory,
        ObjectManager $manager,
        SingleResourceProviderInterface $singleResourceProvider,
        ResourcesCollectionProviderInterface $resourcesFinder,
        ResourceFormFactoryInterface $resourceFormFactory,
        RedirectHandlerInterface $redirectHandler,
        FlashHelperInterface $flashHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        EventDispatcherInterface $eventDispatcher,
        ?StateMachineInterface $stateMachine,
        ResourceUpdateHandlerInterface $resourceUpdateHandler,
        ResourceDeleteHandlerInterface $resourceDeleteHandler,
        VendorProviderInterface $vendorProvider
    ) {
        parent::__construct(
            $metadata,
            $requestConfigurationFactory,
            $viewHandler,
            $repository,
            $factory,
            $newResourceFactory,
            $manager,
            $singleResourceProvider,
            $resourcesFinder,
            $resourceFormFactory,
            $redirectHandler,
            $flashHelper,
            $authorizationChecker,
            $eventDispatcher,
            $stateMachine,
            $resourceUpdateHandler,
            $resourceDeleteHandler
        );

        $this->vendorProvider = $vendorProvider;
    }

    public function createAction(Request $request): Response
    {
        try {
            return parent::createAction($request);
        } catch (ShopUserNotFoundException $exception) {
            return $this->redirectToRoute('sylius_shop_login');
        } catch (TokenNotFoundException $exception) {
            return $this->redirectToRoute('sylius_shop_login');
        }
    }

    public function updateAction(Request $request): Response
    {
        $vendor = $this->vendorProvider->provideCurrentVendor();
        $pendingUpdate = $this->manager->getRepository(VendorProfileUpdate::class)
            ->findOneBy(['vendor' => $vendor]);

        if (null === $pendingUpdate) {
            return parent::updateAction($request);
        }

        $this->addFlash('error', 'sylius.user.verify_email_request');

        return $this->redirectToRoute('vendor_profile');
    }

    public function showAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        /** @var ResourceInterface $resource */
        $resource = $this->vendorProvider->provideCurrentVendor();
        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $resource);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::SHOW . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $resource,
                $this->metadata->getName() => $resource,
            ]);
        }

        return $this->createRestView($configuration, $resource);
    }

    public function verifyVendorAction(Request $request): Response
    {
        $vendorId = $request->attributes->get('id', 0);

        $currentVendor = $this->manager->getRepository(Vendor::class)
            ->findOneBy(['id' => $vendorId]);

        if (null === $currentVendor) {
            throw new NotFoundHttpException(sprintf('Vendor with id %d has not been found', $vendorId));
        }

        $currentVendor = $this->manager->getRepository(Vendor::class)->findOneBy(['id' => $vendorId]);
        if ($currentVendor)
            $currentVendor->setStatus(VendorInterface::STATUS_VERIFIED);

        $this->manager->flush();

        $this->addFlash('success', 'bitbag_mvm_plugin.ui.vendor_verified');

        return $this->redirectToRoute('bitbag_mvm_plugin_admin_vendor_index');
    }

    public function enablingVendorAction(Request $request): Response
    {
        $currentVendor = $this->manager->getRepository(Vendor::class)->findOneBy(['id' => $request->attributes->get('id')]);
        if ($currentVendor) {
            $currentVendor->setEnabled(!$currentVendor->isEnabled());
            $messageSuffix = $currentVendor->isEnabled() ? 'enabled' : 'disabled';


            $this->manager->flush();
            $this->addFlash('success', 'bitbag_mvm_plugin.ui.vendor_' . $messageSuffix);
        }
        return $this->redirectToRoute('bitbag_mvm_plugin_admin_vendor_index');
    }
}
