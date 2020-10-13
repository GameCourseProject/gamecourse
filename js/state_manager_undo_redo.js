
class StateManager{
    //save the objects
    constructor(state){
        this.old = [];

        var dpState = Object.assign({}, state);
        this.present = dpState;

        this.future = [];
    }
    
    undo(){
        //present passa para future
        this.future.push(this.present);
        
        //ultimo do old passa para present
        var state = this.old.pop();
        this.present = state;

        return this.present;
    }

    canUndo(){
        if (this.old.length != 0) return true;
        else return false;
    }

    redo(){
        //present passa para fim do old
        this.old.push(this.present);

        //primeiro do future passa para present
        var state = this.future.pop();
        this.present = state;

        return this.present;
    }

    canRedo(){
        if (this.future.length != 0) return true;
        else return false;
    }

    newState(state){
        //present passa para fim do old
        this.old.push(this.present);

        var dpState = Object.assign({}, state);
        this.present = dpState;
        
        //elimina todos os states do future
        this.future = [];
    }

    resetSate(state){
        //elimina todos os states do old
        this.old = [];

        var dpState = Object.assign({}, state);
        this.present = dpState;
        //elimina todos os states do future
        this.future = [];

    }

}