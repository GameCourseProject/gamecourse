import {Component, ElementRef, Input, OnInit, QueryList, ViewChild, ViewChildren} from '@angular/core';

import {ViewBlock} from "../../../_domain/views/view-types/view-block";
import {ViewMode} from "../../../_domain/views/view";
import { DragDrop, DragRef, moveItemInArray } from '@angular/cdk/drag-drop';
import { groupedChildren } from 'src/app/_domain/views/build-view-tree/build-view-tree';

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;

  classes: string;
  children: string;

  @ViewChild('dropList', { static: false }) dropListRef: ElementRef;
  @ViewChildren('dragItem') dragItems: QueryList<ElementRef>;
  private dragRefs: DragRef[] = new Array<DragRef>();

  constructor(
    private dragDropService: DragDrop,
  ) { }

  ngOnInit(): void {
    this.classes = 'bb-block bb-block-' + this.view.direction;
    if (this.view.columns) this.classes += ' bb-block-cols-' + this.view.columns;
    if (this.view.responsive) this.classes += ' bb-block-responsive'
    this.children = 'bb-block-children';

    // Include certain classes on parent
    if (this.view.classList) {
      for (const cl of this.view.classList.split(' ')) {
        if (cl.startsWith('rounded')) this.classes += ' ' + cl;
        if (cl === 'flex-wrap') this.classes += ' bb-block-wrap';
      }
    }
  }

  public ngAfterViewInit() {
    const dropListRef = this.dragDropService.createDropList(this.dropListRef);

    dropListRef.withOrientation(this.view.direction);

    this.dragItems.toArray().forEach(element => {
      let dragRef = this.dragDropService.createDrag(element);
      this.dragRefs.push(dragRef);
    });
    
    dropListRef.withItems(this.dragRefs);

    dropListRef.beforeStarted.subscribe(event => {
      this.dropListRef.nativeElement.classList.add('cdk-drop-list-dragging');
      this.dropListRef.nativeElement.classList.add('cdk-drag-animating');
    });
    
    dropListRef.dropped.subscribe(event => {
      this.drop(event);
      this.dropListRef.nativeElement.classList.remove('cdk-drop-list-dragging');
      this.dropListRef.nativeElement.classList.remove('cdk-drag-animating');
    });
  }

  drop(event: any) {
    moveItemInArray(this.view.children, event.previousIndex, event.currentIndex);
    const group = groupedChildren.get(this.view.id);
    moveItemInArray(group, event.previousIndex, event.currentIndex);
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }
}