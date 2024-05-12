import {Component, Input, OnInit} from '@angular/core';
import {BlockDirection, ViewBlock} from "../../../_domain/views/view-types/view-block";
import {View, ViewMode} from "../../../_domain/views/view";
import {
  CdkDragDrop,
  CdkDrag,
  CdkDropList,
  moveItemInArray,
  transferArrayItem,
  CdkDragEnter,
  CdkDragExit,
  CdkDragStart,
  DragRef,
  DropListRef,
} from '@angular/cdk/drag-drop';
import { groupedChildren } from 'src/app/_domain/views/build-view-tree/build-view-tree';
import {HistoryService} from "../../../_services/history.service";
import {ViewEditorService} from "../../../_services/view-editor.service";
import * as _ from "lodash";
import {ViewSelectionService} from "../../../_services/view-selection.service";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;

  classes: string;
  children: string;

  constructor(
    public selection: ViewSelectionService,
    public service: ViewEditorService,
    public history: HistoryService
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

  drop (event: CdkDragDrop<View[]>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );
      const group = groupedChildren.get(this.view.id);
      moveItemInArray(group, event.previousIndex, event.currentIndex);

      if (event.previousIndex != event.currentIndex) {
        this.history.saveState({
          viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
          groupedChildren: groupedChildren
        });
      }

    } else {
      transferArrayItem(
        event.previousContainer.data,
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );
      // TODO: move in grouped children
    }
  }

  private getIdsRecursive(item: any): string[] {
    let ids = [];
    if ('children' in item) {
      ids.push(String(item.id));
      item.children.forEach(childItem => {
        ids = ids.concat(this.getIdsRecursive(childItem));
      });
    }
    return ids;
  }

  public get connectedTo(): string[] {
    let view: View = this.view;
    while (view.parent) {
      view = view.parent;
    }
    return this.getIdsRecursive(view).reverse();
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  protected readonly String = String;
  protected readonly BlockDirection = BlockDirection;
}
