<?hh
abstract class :base:element extends :x:element {

  abstract protected function compose();

  final public function addClass($class) {
    $this->setAttribute(
      'class',
      trim($this->getAttribute('class').' '.$class)
    );
    return $this;
  }

  final protected function render() {
    $root = $this->compose();
    if ($root === null) {
      return <x:frag />;
    }
    if (:x:base::$ENABLE_VALIDATION) {
      if (!$root instanceof :xhp:html-element
          && !$root instanceof :ui:base) {
        throw new XHPClassException(
          $this,
          'compose() must return an xhp:html-element'.
          ' or ui:base instance.'
        );
      }
    }

    // Get all attributes declared on this instance
    $attributes = $this->getAttributes();
    // Get all allowed attributes on the node returned
    // from compose()
    $declared = $root->__xhpAttributeDeclaration();

    // Transfer any classes that were added inline over
    // to the root node.
    if (array_key_exists('class', $attributes)) {
      $attributes['class'] && $root->addClass($attributes['class']);
      unset($attributes['class']);
    }

    // Always forward data and aria attributes
    $html5Attributes = array('data-' => true, 'aria-' => true);

    // Transfer all valid attributes to $root
    foreach ($attributes as $attribute => $value) {
      if (isset($declared[$attribute]) ||
          isset($html5Attributes[substr($attribute, 0, 5)])) {
        try {
          $root->setAttribute($attribute, $value);
        } catch (XHPInvalidAttributeException $e) {
          // This happens when the attribute defined on
          // your instance has a different definition
          // than the one you've returned. This usually
          // happens when you've defined different enum values.
          // When you turn off validation (like on prod) these
          // errors will not be thrown, so you should
          // fix your APIs to use different attributes.
          error_log(
            'Attribute name collision for '.$attribute.
            ' in :ui:base::render() when transferring'.
            ' attributes to a returned node. source: '.
            $this->source."\nException: ".$e->getMessage()
          );
        }
      }
    }

    return $root;
  }

}
