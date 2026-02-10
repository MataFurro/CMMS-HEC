
export enum WOStatus {
  PENDING = 'PENDING',
  IN_PROGRESS = 'IN_PROGRESS',
  COMPLETED = 'COMPLETED',
  CANCELLED = 'CANCELLED'
}

export enum AssetCriticality {
  CRITICAL = 'Crítico',
  RELEVANT = 'Relevante',
  LOW = 'Bajo',
  NA = 'No Aplica'
}

export interface Attachment {
  name: string;
  type: 'pdf' | 'image' | 'doc';
  size: string;
  url: string;
}

export interface Asset {
  id: string;
  serialNumber: string;
  name: string;
  brand: string;
  model: string;
  location: string;
  subLocation?: string;
  vendor?: string;
  serviceProvider?: string;
  ownership?: string;
  criticality: AssetCriticality;
  status: 'OPERATIVE' | 'MAINTENANCE' | 'OUT_OF_SERVICE';
  usefulLife: number; // percentage
  yearsRemaining: number;
  warrantyUntil: string;
  warrantyExpiration?: string;
  warrantyStatus?: string;
  underMaintenancePlan: boolean;
  imageUrl: string;
}

export enum UserRole {
  TECHNICIAN = 'TECHNICIAN',
  ENGINEER = 'ENGINEER',
  CHIEF_ENGINEER = 'CHIEF_ENGINEER',
  AUDITOR = 'AUDITOR'
}

export interface User {
  id: string;
  name: string;
  role: UserRole;
  avatar?: string;
}

export interface WorkOrder {
  id: string;
  assetId: string;
  assetName: string;
  type: 'Preventivo' | 'Correctivo' | 'Calibración';
  status: WOStatus;
  priority: 'Alta' | 'Media' | 'Baja';
  technician: string;
  requester: string;
  date: string;
  description: string;
  location: string;
  attachments?: Attachment[];
}
