<?php

namespace Drupal\opigno_commerce\Controller;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce\commerce_product;
use Drupal\commerce_cart;
use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for products routes.
 */
class SubscribeWithPaymentController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new SubscribeWithPaymentController object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(CartManagerInterface $cart_manager,CartProviderInterface $cart_provider) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * @param Group $group
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function subscribeWithPayment(Group $group) {
    // Check if training is already bought.
    $user = $this->currentUser();
    $is_bought = opingo_commerce_check_if_training_bought($group->id(), $user->id());
    if ($is_bought) {
      return [
        '#markup' => $this->t('Training @training is already bought. Check your orders.', [
          '@training' => $group->label(),
        ]),
      ];
    }
    // Add product to cart.
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
    $productObj = $storage->loadByProperties(['field_learning_path_id' => $group->id()]);
    $productObj = reset($productObj);

    $product_variation_id = $productObj->get('variations')
      ->getValue()[0]['target_id'];
    $storeId = $productObj->get('stores')->getValue()[0]['target_id'];
    $variationobj = \Drupal::entityTypeManager()
      ->getStorage('commerce_product_variation')
      ->load($product_variation_id);
    $store = \Drupal::entityTypeManager()
      ->getStorage('commerce_store')
      ->load($storeId);

    $cart = $this->cartProvider->getCart('default', $store);

    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store);
    }

    // Check if item is already in cart.
    $cart_items = $cart->getItems();
    if ($cart_items) {
      foreach ($cart_items as $item) {
        /* @var Drupal\commerce_product\Entity\ProductVariation $product_variation */
        $item_variation = $item->getPurchasedEntity();
        if ($item_variation->id() == $product_variation_id) {
          return [
            '#markup' => $this->t('Training @training is already added to @cart', [
              '@training' => $group->label(),
              '@cart' => Link::createFromRoute('your cart', 'commerce_cart.page')->toString(),
            ]),
          ];
        }
      }
    }

    // Process to place order programatically.
    /* @var \Drupal\commerce_cart\CartManager $cart_manager */
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $line_item = $cart_manager->addEntity($cart, $variationobj);

//    $response = new RedirectResponse(Url::fromRoute('commerce_cart.page')->toString());
//    return $response;
    return [
      '#markup' => $this->t('Training @training was added to @cart', [
        '@training' => $group->label(),
        '@cart' => Link::createFromRoute(t('your cart'), 'commerce_cart.page')->toString(),
      ]),
    ];

  }

  /**
   * Page title callback.
   *
   * @param \Drupal\group\Entity\Group $group
   *
   * @return string
   *   Training entity label.
   */
  public function formTitleCallback(Group $group) {
    // Return entity label.
    return 'Buy access to training - ' . $group->label();
  }

}
