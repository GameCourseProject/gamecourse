import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

@Component({
  selector: 'app-docs-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

  @Input() tabs: {id: string, text: string}[];
  @Input() isFunctions: boolean = false;

  @Output() onTabClicked: EventEmitter<{id: string, index: number}> = new EventEmitter<{id: string, index: number}>();

  constructor() { }

  ngOnInit(): void {
  }

}
