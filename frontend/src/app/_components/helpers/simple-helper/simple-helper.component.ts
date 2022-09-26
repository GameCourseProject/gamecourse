import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-simple-helper',
  templateUrl: './simple-helper.component.html'
})
export class SimpleHelperComponent implements OnInit {

  @Input() text: string;
  @Input() position: 'top' | 'bottom' | 'left' | 'right'

  constructor() { }

  ngOnInit(): void {
  }

  get Position(): string {
    if (this.position === 'top') return '';
    if (this.position === 'bottom') return 'tooltip-bottom';
    if (this.position === 'left') return 'tooltip-left';
    if (this.position === 'right') return 'tooltip-right';
    return '';
  }

}
