import { Injectable } from '@angular/core';
import { Aspect } from '../_domain/views/aspects/aspect';
import { View, ViewMode } from '../_domain/views/view';
import * as _ from "lodash"
import { addToGroupedChildren, getFakeId, groupedChildren, viewsDeleted } from '../_domain/views/build-view-tree/build-view-tree';
import { Subject } from 'rxjs';
import { ViewTable } from '../_domain/views/view-types/view-table';


/**
 * This service can keep information about and manipulate the
 * several views/aspects of a page/template
 *
 * IMPORTANT NOTE: the edit action is inside the component-editor
 * component, not here, due to its complexity
 */
@Injectable({
  providedIn: 'root'
})
export class ViewEditorService {
  public viewsByAspect: { aspect: Aspect, view: View | null }[];
  public selectedAspect: Aspect;  // Selected aspect for previewing and editing
  public rolesHierarchy;
  public aspectsToDelete: Aspect[] = [];
  public aspectsToAdd: Aspect[] = [];
  public aspectsToChange: { old: Aspect, newAspect: Aspect }[] = [];

  selectedChange: Subject<View> = new Subject<View>();

  constructor() { }

  getSelectedView(): View {
    return this.viewsByAspect.find((e) => _.isEqual(e.aspect, this.selectedAspect))?.view;
  }

  /*********************************** ASPECTS MANAGEMENT  ***********************************/

  getEntryOfAspect(aspect: Aspect) {
    return this.viewsByAspect.find((e) => _.isEqual(e.aspect, aspect));
  }

  isMoreSpecific(role: string | null, antecessor: string | null): boolean {
    if (role && !antecessor || role === antecessor) {
      return true;
    }
    else if (this.rolesHierarchy[role]?.parent) {
      return this.isMoreSpecific(this.rolesHierarchy[role].parent._name, antecessor);
    }
    else {
      return false;
    }
  }

  deleteAspect(aspect: Aspect) {
    this.viewsByAspect = this.viewsByAspect.filter(e => e.aspect.userRole !== aspect.userRole || e.aspect.viewerRole !== aspect.viewerRole);
  }

  createAspect(aspect: Aspect) {
    this.viewsByAspect.push({ aspect: aspect, view: _.cloneDeep(this.viewsByAspect[0].view) }); // FIXME: should be the view of the most similar, less specific, aspect
  }

  changeAspect(old: Aspect, newAspect: Aspect) {
    this.viewsByAspect = this.viewsByAspect.map(e => {
      if (_.isEqual(e.aspect, old)) {
        e.view.modifyAspect(old, newAspect);
        return {aspect: newAspect, view: e.view}
      }
      else return e
    })
  }

  applyAspectChanges() {
    // Delete aspects
    for (let deleted of this.aspectsToDelete) {
      this.deleteAspect(deleted);
    }
    this.aspectsToDelete = [];

    // Modify roles of existing aspects
    for (let changed of this.aspectsToChange) {
      if (changed.old.viewerRole === "new" || changed.old.userRole === "new") continue;
      this.changeAspect(changed.old, changed.newAspect);
    }
    this.aspectsToChange = [];

    // Create new aspects
    for (let newAspect of this.aspectsToAdd) {
      this.createAspect(newAspect);
    }
    this.aspectsToAdd = [];
  }


  /*********************************** VIEWS ACTIONS  ***********************************/

  /*
   * Adds a view to another view
   * Option to add it by reference or to create a copy (by value)
   * If to is null, it adds it as root
   */
  add(item: View, to: View | null, mode: "value" | "ref" = "ref") {
    // All Aspects that should display the new item (this one and all others beneath in hierarchy)
    const toAdd = this.viewsByAspect.filter((e) =>
      (e.aspect.userRole === this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.viewerRole, this.selectedAspect.viewerRole))
      || (e.aspect.userRole !== this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.userRole, this.selectedAspect.userRole))
    );

    let newItem;
    if (item instanceof ViewTable) {
      newItem = new ViewTable(ViewMode.EDIT, item.id, item.viewRoot, null, this.selectedAspect, item.footers, item.searching,
        item.columnFiltering, item.paging, item.lengthChange, item.info, item.ordering, item.orderingBy,
        item.headerRows.concat(item.bodyRows), item.cssId, item.classList, item.styles, item.visibilityType,
        item.visibilityCondition, item.loopData, item.variables, item.events);
    }
    else {
      newItem = _.cloneDeep(item);
      newItem.aspect = this.selectedAspect;
    }
    newItem.switchMode(ViewMode.EDIT);

    if (mode === "value") newItem.replaceWithFakeIds();

    // Add to a view
    if (to) {
      for (let el of toAdd) {
        let itemToAdd = _.cloneDeep(newItem);
        el.view?.findView(to.id)?.addChildViewToViewTree(itemToAdd);
      }
      addToGroupedChildren(newItem, to.id);
    }
    // Add as root
    else {
      this.viewsByAspect = this.viewsByAspect.map(e => {
        if (toAdd.includes(e)) {
          let itemToAdd = _.cloneDeep(newItem);
          return { aspect: e.aspect, view: itemToAdd };
        }
        else return e
      })
      addToGroupedChildren(newItem, null);
      this.selectedChange.next(this.getSelectedView());
    }
  }

  /*
   * Deletes a view
   */
  delete(item: View) {
    const viewsWithThis = this.viewsByAspect.filter((e) => e.view.findView(item.id));

    const lowerInHierarchy = viewsWithThis.filter((e) =>
      (e.aspect.userRole === this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.viewerRole, this.selectedAspect.viewerRole))
      || (e.aspect.userRole !== this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.userRole, this.selectedAspect.userRole))
    );

    for (let el of lowerInHierarchy) {
      if (el.view?.id === item.id) {
        this.viewsByAspect.splice(this.viewsByAspect.findIndex(e => _.isEqual(el.aspect, e.aspect)), 1, { aspect: el.aspect, view: null });
      }
      else {
        let view = el.view.findView(item.id);
        if (view.parent) {
          view.parent.removeChildView(item.id);
        }
      }
    }

    // View doesn't exist anymore in any tree -> delete from database
    if (item.id > 0 && this.viewsByAspect.filter((e) => e.view?.findView(item.id)).length <= 0) {
      viewsDeleted.push(item.id);
    }

    if (item.parent) {
      let entry = groupedChildren.get(item.parent.id);
      entry.forEach((group, groupIndex) => {
        const itemIndex = group.indexOf(item.id);
        if (itemIndex >= 0) {
          group.splice(itemIndex, 1);
          if (group.length <= 0) {
            entry.splice(groupIndex, 1);
            groupedChildren.set(item.parent.id, entry);
          }
          else {
            groupedChildren.set(item.parent.id, entry);
          }
        }
      })
    }
    else {
      // this is the root
      groupedChildren.delete(item.id);
    }
  }

  /*
   * Duplicates a view
   */
  duplicate(item: View) {
    this.add(item, item.parent, "value")
  }
}
