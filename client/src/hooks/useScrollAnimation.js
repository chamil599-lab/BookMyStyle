import { useEffect, useRef, useCallback, useState } from 'react';

/**
 * Hook that adds scroll-triggered animations using Intersection Observer.
 * Adds 'animated' class to elements with 'scroll-animate' class when they enter viewport.
 * Uses MutationObserver to detect dynamically added elements.
 */
export default function useScrollAnimation(options = {}) {
  const [container, setContainer] = useState(null);
  const observerRef = useRef(null);
  const containerRef = useCallback((node) => {
    setContainer(node);
  }, []);

  const observeElement = useCallback((el) => {
    if (observerRef.current && !el.classList.contains('animated')) {
      observerRef.current.observe(el);
    }
  }, []);

  useEffect(() => {
    if (!container) return;

    // Create Intersection Observer
    observerRef.current = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animated');
          observerRef.current.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px',
      ...options,
    });

    // Observe existing elements
    container.querySelectorAll('.scroll-animate').forEach(observeElement);

    // Watch for dynamically added elements (e.g. after data loads)
    const mutationObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1) {
            if (node.classList.contains('scroll-animate')) {
              observeElement(node);
            }
            node.querySelectorAll?.('.scroll-animate').forEach(observeElement);
          }
        });
      });
    });

    mutationObserver.observe(container, { childList: true, subtree: true });

    return () => {
      observerRef.current?.disconnect();
      mutationObserver.disconnect();
    };
  }, [container, observeElement]);

  return containerRef;
}
