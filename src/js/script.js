gsap.registerPlugin(ScrollTrigger,ScrollSmoother,ScrollToPlugin,SplitText);

ScrollSmoother.create({
	smooth: 1, // how long (in seconds) it takes to "catch up" to the native scroll position
	effects: true, // looks for data-speed and data-lag attributes on elements
});