import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {BlockDirection, ViewBlock} from "../../../_domain/views/view-types/view-block";
import {View, ViewMode} from "../../../_domain/views/view";
import {CdkDragDrop, moveItemInArray} from '@angular/cdk/drag-drop';
import {
  groupedChildren,
  viewsDeleted
} from 'src/app/_domain/views/build-view-tree/build-view-tree';
import {HistoryService} from "../../../_services/history.service";
import {ViewEditorService} from "../../../_services/view-editor.service";
import * as _ from "lodash";
import {ViewSelectionService} from "../../../_services/view-selection.service";
import {ModalService} from "../../../_services/modal.service";
import {installPatch} from "./nested-drag-drop-patch";
import {ViewCollapse} from "../../../_domain/views/view-types/view-collapse";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;
  @Output() addComponentEvent = new EventEmitter<void>();

  classes: string;
  children: string;

  constructor(
    public selection: ViewSelectionService,
    public service: ViewEditorService,
    public history: HistoryService
  ) { }

  ngOnInit(): void {
    installPatch();

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

  /*** ------------------------------------------------ ***/
  /*** ----------------- Drag and Drop ---------------- ***/
  /*** ------------------------------------------------ ***/

  drop (event: CdkDragDrop<View[]>) {
    const lowerInHierarchy = this.service.lowerInHierarchy(this.view);
    lowerInHierarchy.push(this.service.getEntryOfAspect(this.service.selectedAspect));

    const higherInHierarchy = this.service.higherInHierarchy(this.view);

    // Reorder inside same block ------------------------------------------------------------------
    if (event.previousContainer === event.container && event.previousIndex != event.currentIndex) {
      if (higherInHierarchy.length <= 0) {
        // just move in current block (which can be also in lower-in-hierarchy views
        for (let el of lowerInHierarchy) {
          let view = el.view.findView(+event.container.id);
          if (view instanceof ViewBlock) {
            moveItemInArray(
              view.children,
              event.previousIndex,
              event.currentIndex
            );
          }
        }
        const group = groupedChildren.get(+event.container.id);
        moveItemInArray(group, event.previousIndex, event.currentIndex);
      }
      else {
        const viewToMove = _.cloneDeep(event.previousContainer.data[event.previousIndex]);

        const newBlock = this.service.delete(event.container.data[event.previousIndex]);

        setTimeout(() => {
          if (newBlock) {
            this.service.add(viewToMove, newBlock, "value");
          }
        }, 0);

        setTimeout(() => {
          for (let el of lowerInHierarchy) {
            let view = el.view.findView(newBlock.id);
            if (view instanceof ViewBlock) {
              moveItemInArray(
                view.children,
                view.children.length - 1,
                event.currentIndex
              );
            }
          }
        }, 0);

        const group = groupedChildren.get(+event.container.id);
        moveItemInArray(group, event.container.data.length - 1, event.currentIndex);
      }
    }
    // Transfer to a different block ------------------------------------------------------------------
    else if (event.previousContainer !== event.container) {
      const viewToMove = _.cloneDeep(event.previousContainer.data[event.previousIndex]);
      this.service.add(viewToMove, this.view, "value");

      for (let el of lowerInHierarchy) {
        let view = el.view.findView(this.view.id);
        if (view instanceof ViewBlock) {
          moveItemInArray(
            view.children,
            view.children.length - 1,
            event.currentIndex
          );
        }
      }

      this.service.delete(event.previousContainer.data[event.previousIndex]);

      const group = groupedChildren.get(+event.container.id);
      moveItemInArray(group, event.container.data.length - 1, event.currentIndex);
    }

    this.history.saveState({
      viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
      groupedChildren: groupedChildren,
      viewsDeleted: viewsDeleted
    });
  }

  private getIdsRecursive(item: any): string[] {
    let ids = [];
    if (item instanceof ViewCollapse) {
      ids = ids.concat(this.getIdsRecursive(item.content));
      ids = ids.concat(this.getIdsRecursive(item.header));
    }
    else if ('children' in item) {
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

  getCantDrag(): boolean {
    return ModalService.isAnyOpen();
  }


  /*** ------------------------------------------------ ***/
  /*** -------------------- Helpers ------------------- ***/
  /*** ------------------------------------------------ ***/

  addComponent(event: any): void {
    event.stopPropagation();
    this.selection.set(this.view);
    this.addComponentEvent.emit();
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  protected readonly String = String;
  protected readonly BlockDirection = BlockDirection;
}
