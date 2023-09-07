import { Directive, HostListener, ElementRef } from '@angular/core';

@Directive({
  selector: '[appScrollableDrag]'
})
export class ScrollableDragDirective {
  private container: HTMLElement;
  private scrollAmount = 50;

  constructor(private el: ElementRef) {
    this.container = el.nativeElement;
  }

  @HostListener('cdkDragMoved', ['$event'])
  onDragMoved(event: any): void {
    // Get the mouse position relative to the container
    const mousePositionY = event.pointerPosition.y - this.container.getBoundingClientRect().top;

    const scrollThreshold = 20;

    // Check if the mouse is near the top of the container
    if (mousePositionY < scrollThreshold) {
      this.container.scrollTop -= this.scrollAmount;
    }

    // Check if the mouse is near the bottom of the container
    const containerHeight = this.container.clientHeight;
    if (containerHeight - mousePositionY < scrollThreshold) {
      this.container.scrollTop += this.scrollAmount;
    }
  }
}
