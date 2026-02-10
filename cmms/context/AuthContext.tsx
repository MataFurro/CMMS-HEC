
import React, { createContext, useContext, useState, ReactNode } from 'react';
import { User, UserRole } from '../types';
import { mockUsers } from '../mockData';

interface AuthContextType {
    currentUser: User | null;
    login: (userId: string) => void;
    logout: () => void;
    hasPermission: (permission: Permission) => boolean;
}

export enum Permission {
    VIEW_INVENTORY = 'VIEW_INVENTORY',
    MANAGE_INVENTORY = 'MANAGE_INVENTORY', // Add, Edit
    VIEW_DASHBOARD = 'VIEW_DASHBOARD',
    MANAGE_WORK_ORDERS = 'MANAGE_WORK_ORDERS', // Create, Approve
    EXECUTE_WORK_ORDERS = 'EXECUTE_WORK_ORDERS', // Fill
    MANAGE_ALL = 'MANAGE_ALL',
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

const ROLE_PERMISSIONS: Record<UserRole, Permission[]> = {
    [UserRole.TECHNICIAN]: [
        Permission.EXECUTE_WORK_ORDERS,
        Permission.VIEW_DASHBOARD // Maybe limited
    ],
    [UserRole.ENGINEER]: [
        Permission.VIEW_INVENTORY,
        Permission.MANAGE_INVENTORY,
        Permission.VIEW_DASHBOARD,
        Permission.MANAGE_WORK_ORDERS
    ],
    [UserRole.CHIEF_ENGINEER]: [
        Permission.VIEW_INVENTORY,
        Permission.MANAGE_INVENTORY,
        Permission.VIEW_DASHBOARD,
        Permission.MANAGE_WORK_ORDERS,
        Permission.EXECUTE_WORK_ORDERS,
        Permission.MANAGE_ALL
    ],
    [UserRole.AUDITOR]: [
        Permission.VIEW_INVENTORY,
        Permission.VIEW_DASHBOARD
    ]
};

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
    // Default to null (Logged Out)
    const [currentUser, setCurrentUser] = useState<User | null>(null);

    const login = (userId: string) => {
        const user = mockUsers.find(u => u.id === userId);
        if (user) setCurrentUser(user);
    };

    const logout = () => setCurrentUser(null);

    const hasPermission = (permission: Permission): boolean => {
        if (!currentUser) return false;
        const permissions = ROLE_PERMISSIONS[currentUser.role];
        return permissions.includes(permission) || permissions.includes(Permission.MANAGE_ALL);
    };

    return (
        <AuthContext.Provider value={{ currentUser, login, logout, hasPermission }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
