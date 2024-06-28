import { View, ViewDatabase } from "../view";
import { ViewBlock } from "../view-types/view-block";
import { ViewCollapse } from "../view-types/view-collapse";
import { ViewRow } from "../view-types/view-row";
import { ViewTable } from "../view-types/view-table";

export let viewTree: any[];                           // The view tree being built
export let viewsAdded: Map<number, ViewDatabase>;     // Holds building-blocks that have already been added to the tree
export let viewsDeleted: number[] = [];               // viewIds of views that were completely deleted -> delete from database
let fakeId: number = -1;                              // Fake, negative ids, for new views, to be generated in backend

export let groupedChildren: Map<number, number[][]>;

export function getFakeId() : number {
  const id = fakeId;
  fakeId -= 1;
  return id;
}

export function setGroupedChildren(value: Map<number, number[][]>) {
  groupedChildren = value;
}

export function setViewsDeleted(value: number[]) {
  viewsDeleted = value;
}

export function initGroupedChildren(viewTree: any[]) {
  groupedChildren = new Map<number, number[][]>();
  for (let view of viewTree) {
    recursiveGroupChildren(view);
  }
}

function recursiveGroupChildren(view: any) {
  if ('children' in view) {
    for (let child of view.children) {
      const group = groupedChildren.get(view.id) ?? [];
      group.push(child.map((e) => e.id));
      groupedChildren.set(view.id, group);
      for (let el of child) {
        recursiveGroupChildren(el);
      }
    }
  }
}

export function addToGroupedChildren(view: View, parentId: number) {
  if (parentId) {
    const group: number[][] = groupedChildren.get(parentId) ?? [];
    group.push([view.id]);
    groupedChildren.set(parentId, group);
  }
  if (view instanceof ViewBlock) {
    for (let child of view.children) {
      addToGroupedChildren(child, view.id);
    }
  }
  else if (view instanceof ViewTable) {
    for (let child of view.headerRows) {
      addToGroupedChildren(child, view.id);
    }
    for (let child of view.bodyRows) {
      addToGroupedChildren(child, view.id);
    }
  }
  else if (view instanceof ViewRow) {
    for (let child of view.children) {
      addToGroupedChildren(child, view.id);
    }
  }
  else if (view instanceof ViewCollapse) {
    addToGroupedChildren(view.header, view.id);
    addToGroupedChildren(view.content, view.id);
  }
}

export function addVariantToGroupedChildren(parentId: number, baseId: number, variantId: number) {
  let entry = groupedChildren.get(parentId);
  if (entry) {
    entry.forEach((group: number[]) => {
      if (group.indexOf(baseId) != -1) {
        group.push(variantId);
        groupedChildren.set(parentId, entry);
      }
    })
  } else {
    console.log("Warning: Entry for parent not found");
    groupedChildren.set(parentId, [[variantId]]);
  }
}

/**
 * Builds a view tree to be sent to database by merging all aspects
 * according to view ids and viewIds.
 *
 * @param viewsOfAspects
 */
export function buildViewTree(viewsOfAspects: View[]): ViewDatabase[] {
  viewsAdded = new Map<number, ViewDatabase>();
  viewTree = [];

  // Go through each aspect and add to view tree
  for (const view of viewsOfAspects) {
    if (view) view.buildViewTree();
  }

  // Clean up nonexistent ids
  // This is specially useful if an aspect was deleted, since while building the tree
  // it won't find a matching view for the ids
  viewTree.forEach(tree => recursiveRemoveNonexistent(tree));

  function recursiveRemoveNonexistent(view: any) {
    if ('children' in view) {
      for (let group of view.children) {
        for (let child of group) {
          if (typeof child === "number") {
            viewsDeleted.push(child);
          }
          else {
            recursiveRemoveNonexistent(child);
          }
        }
        view.children.splice(view.children.indexOf(group), 1, group.filter(e => typeof e !== "number"));
        view.children = view.children.filter(e => e.length > 0);
      }
    }
  }

  return viewTree;
}
