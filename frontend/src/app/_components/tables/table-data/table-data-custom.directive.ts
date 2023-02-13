import { Directive, ViewContainerRef } from '@angular/core';

@Directive({
  selector: '[componentContainer]'
})
export class TableDataCustomDirective {

  constructor(public viewContainerRef: ViewContainerRef) { }

}
