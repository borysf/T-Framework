<?php
namespace System\Component;

use System\Web\Page\Control\Event\TEventArgs;

/**
 * Base class for all components. Components are classes supporting events.
 * To declare events in your sub class, you need to create event methods, i.e. 
 * `protected function onCreate(?TEventArgs $args)` where `onCreate` is your
 * event name. Event names must start with the `on` prefix.
 */
abstract class TComponent {
    private ?array $__eventHandlers = null;

    /**
     * Returns a callable closure to the inner method if accessed by a property name.
     * Primarily used by the framework internally to assign callback methods to the
     * event. If no method exists, throws `TComponentException`.
     */
    public function __get(string $name) : mixed {
        if (method_exists($this, $name)) {
            return function(...$args) use ($name) { $this->$name(...$args); };
        }

        throw new TComponentException($this, 'accessing undefined property: '.$name);
    }

    /**
     * Throws `TComponent` exception while trying to access undefined property.
     */
    public function __set(string $name, $value) : void {
        throw new TComponentException($this, 'accessing undefined property: '.$name);
    }

    /** 
     * Checks if component supports given event.
     */
    final protected function eventExists(string $eventName) : bool {
        return $this->__isEventLike($eventName) && method_exists($this, $eventName);
    }

    /**
     * Adds callable callback as a listener to given event. Callback signature must be 
     * as follows: `callback(TComponent $sender, ?TEventArgs $args)`
     */
    final public function on(string $eventName, callable $callback) : void {
        if (!$this->eventExists($eventName)) {
            throw new TComponentException($this, 'no such event: '.$eventName);
        }

        if (!isset($this->__eventHandlers)) {
            $this->__eventHandlers = [];
        }
        if (!isset($this->__eventHandlers[$eventName])) {
            $this->__eventHandlers[$eventName] = [$callback];
        } else {
            $this->__eventHandlers[$eventName][] = $callback;
        }
    }

    /**
     * Raises given event.
     */
    final public function raise(string $eventName, ?TEventArgs $args = null) : void {
        if (!$this->eventExists($eventName)) {
            throw new TComponentException($this, 'no such event: '.$eventName);
        }

        $this->$eventName($args);
        if (isset($this->__eventHandlers[$eventName])) foreach ($this->__eventHandlers[$eventName] as $v) {
            $v($this, $args);
        }
    }

    private function __isEventLike(string $name) : bool {
        return strtolower(substr($name, 0, 2)) == 'on' && strlen($name) > 2;
    }
}